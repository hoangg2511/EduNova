<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletConfig;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class WalletService
{
    private const DEFAULT_CURRENCY = 'COIN';

    // ─────────────────────────────────────────────────────────────────────────
    // WALLET HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Lấy hoặc tạo ví cho user (tự động tạo ví COIN nếu chưa có).
     */
    public function getOrCreateWallet(int $userId, string $currency = self::DEFAULT_CURRENCY): Wallet
    {
        return Wallet::firstOrCreate(
            ['user_id' => $userId, 'currency_code' => $currency],
            ['balance' => 0, 'status' => 'active']
        );
    }

    public function getBalance(int $userId, string $currency = self::DEFAULT_CURRENCY): int
    {
        return $this->getOrCreateWallet($userId, $currency)->balance;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // EARN
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Cộng coin cho user (EARN), có kiểm tra daily_limit chống spam.
     *
     * @throws RuntimeException nếu vượt giới hạn earn trong ngày
     */
    public function earn(
        int $userId,
        int $amount,
        string $description,
        ?Model $reference = null,
        string $currency = self::DEFAULT_CURRENCY
    ): WalletTransaction {
        if ($amount <= 0) {
            throw new RuntimeException('Số coin earn phải lớn hơn 0.');
        }

        return DB::transaction(function () use ($userId, $amount, $description, $reference, $currency) {
            $wallet = Wallet::where('user_id', $userId)
                ->where('currency_code', $currency)
                ->lockForUpdate()
                ->first();

            if (!$wallet) {
                $wallet = Wallet::create([
                    'user_id'       => $userId,
                    'currency_code' => $currency,
                    'balance'       => 0,
                    'status'        => 'active',
                ]);
                $wallet = Wallet::where('id', $wallet->id)->lockForUpdate()->first();
            }

            if ($wallet->isLocked()) {
                throw new RuntimeException('Ví đã bị khóa, không thể nhận coin.');
            }

            // ── Check daily limit ──────────────────────────────────────────
            $dailyLimit = WalletConfig::get('daily_limit.earn', 0);
            if ($dailyLimit > 0) {
                $earnedToday = WalletTransaction::where('wallet_id', $wallet->id)
                    ->where('type', WalletTransaction::TYPE_EARN)
                    ->whereDate('created_at', today())
                    ->sum('amount');

                if ($earnedToday + $amount > $dailyLimit) {
                    $allowed = max(0, $dailyLimit - $earnedToday);
                    Log::warning('Wallet earn blocked: daily limit exceeded', [
                        'user_id'      => $userId,
                        'requested'    => $amount,
                        'earned_today' => $earnedToday,
                        'daily_limit'  => $dailyLimit,
                    ]);

                    if ($allowed <= 0) {
                        throw new RuntimeException("Bạn đã đạt giới hạn nhận coin trong ngày ({$dailyLimit}).");
                    }
                    // Cộng phần còn lại được phép (tùy chọn — có thể đổi thành throw cứng nếu muốn chặn hẳn)
                    $amount = $allowed;
                }
            }

            $wallet->increment('balance', $amount);
            $wallet->refresh();

            return WalletTransaction::create([
                'wallet_id'      => $wallet->id,
                'user_id'        => $userId,
                'amount'         => $amount,
                'balance_after'  => $wallet->balance,
                'type'           => WalletTransaction::TYPE_EARN,
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id'   => $reference?->id,
                'description'    => $description,
            ]);
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SPEND
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Trừ coin của user (SPEND).
     *
     * @throws RuntimeException nếu không đủ số dư hoặc ví bị khóa
     */
    public function spend(
        int $userId,
        int $amount,
        string $description,
        ?Model $reference = null,
        string $currency = self::DEFAULT_CURRENCY
    ): WalletTransaction {
        if ($amount <= 0) {
            throw new RuntimeException('Số coin spend phải lớn hơn 0.');
        }

        return DB::transaction(function () use ($userId, $amount, $description, $reference, $currency) {
            $wallet = Wallet::where('user_id', $userId)
                ->where('currency_code', $currency)
                ->lockForUpdate()
                ->first();

            if (!$wallet) {
                throw new RuntimeException('Người dùng chưa có ví.');
            }

            if ($wallet->isLocked()) {
                throw new RuntimeException('Ví đã bị khóa, không thể chi tiêu.');
            }

            if ($wallet->balance < $amount) {
                throw new RuntimeException("Số dư không đủ. Hiện có {$wallet->balance}, cần {$amount}.");
            }

            $wallet->decrement('balance', $amount);
            $wallet->refresh();

            return WalletTransaction::create([
                'wallet_id'      => $wallet->id,
                'user_id'        => $userId,
                'amount'         => -$amount, // lưu âm để dễ tổng hợp
                'balance_after'  => $wallet->balance,
                'type'           => WalletTransaction::TYPE_SPEND,
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id'   => $reference?->id,
                'description'    => $description,
            ]);
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // REFUND
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Hoàn tiền cho user (REFUND) — ví dụ tài liệu lỗi sau khi đã trừ coin tải.
     */
    public function refund(
        int $userId,
        int $amount,
        string $description,
        ?Model $reference = null,
        string $currency = self::DEFAULT_CURRENCY
    ): WalletTransaction {
        if ($amount <= 0) {
            throw new RuntimeException('Số coin refund phải lớn hơn 0.');
        }

        return DB::transaction(function () use ($userId, $amount, $description, $reference, $currency) {
            $wallet = $this->getOrCreateWallet($userId, $currency);
            $wallet = Wallet::where('id', $wallet->id)->lockForUpdate()->first();

            $wallet->increment('balance', $amount);
            $wallet->refresh();

            return WalletTransaction::create([
                'wallet_id'      => $wallet->id,
                'user_id'        => $userId,
                'amount'         => $amount,
                'balance_after'  => $wallet->balance,
                'type'           => WalletTransaction::TYPE_REFUND,
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id'   => $reference?->id,
                'description'    => $description,
            ]);
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ADMIN ADJUST
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Admin điều chỉnh thủ công số dư (có thể âm hoặc dương).
     */
    public function adminAdjust(
        int $userId,
        int $amount, // có thể âm hoặc dương
        string $description,
        int $performedBy,
        string $currency = self::DEFAULT_CURRENCY
    ): WalletTransaction {
        if ($amount === 0) {
            throw new RuntimeException('Số coin điều chỉnh không được bằng 0.');
        }

        return DB::transaction(function () use ($userId, $amount, $description, $performedBy, $currency) {
            $wallet = $this->getOrCreateWallet($userId, $currency);
            $wallet = Wallet::where('id', $wallet->id)->lockForUpdate()->first();

            $newBalance = $wallet->balance + $amount;
            if ($newBalance < 0) {
                throw new RuntimeException('Điều chỉnh sẽ khiến số dư âm — không hợp lệ.');
            }

            $wallet->update(['balance' => $newBalance]);

            return WalletTransaction::create([
                'wallet_id'      => $wallet->id,
                'user_id'        => $userId,
                'amount'         => $amount,
                'balance_after'  => $wallet->balance,
                'type'           => WalletTransaction::TYPE_ADMIN_ADJUST,
                'description'    => $description,
                'performed_by'   => $performedBy,
            ]);
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DOMAIN-SPECIFIC SHORTCUTS (tiện dùng trong controller)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Thưởng coin khi tài liệu được duyệt (approved).
     */
    public function rewardDocumentApproved(User $uploader, Model $document, string $docName): WalletTransaction
    {
        $rate = WalletConfig::get('earning_rate.document_upload', 50);
        return $this->earn(
            $uploader->id,
            $rate,
            "Thưởng upload tài liệu: {$docName}",
            $document
        );
    }

    /**
     * Trừ coin khi user tải tài liệu.
     */
    public function chargeDocumentDownload(User $user, Model $document, string $docName): WalletTransaction
    {
        $rate = WalletConfig::get('spending_rate.document_download', 5);
        return $this->spend(
            $user->id,
            $rate,
            "Tải tài liệu: {$docName}",
            $document
        );
    }

    /**
     * Lấy lịch sử giao dịch (phân trang) của user.
     */
    public function history(int $userId, string $currency = self::DEFAULT_CURRENCY, int $perPage = 20)
    {
        $wallet = $this->getOrCreateWallet($userId, $currency);

        return $wallet->transactions()
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }
    public function buyTokenPack(int $userId): UserLog
    {
        $coinCost = WalletConfig::get('conversion.token_pack_coin_cost', 20);
        $tokenAmount = WalletConfig::get('conversion.token_pack_amount', 500);

        $this->spend($userId, $coinCost, 'Mua gói token chat', null);

        $userLog = UserLog::where('user_id', $userId)->firstOrFail();
        $userLog->increment('token_limit', $tokenAmount);

        return $userLog;
    }

    public function buyDownloadPack(int $userId): UserLog
    {
        $coinCost = WalletConfig::get('conversion.download_pack_coin_cost', 15);
        $downloadAmount = WalletConfig::get('conversion.download_pack_amount', 5);

        $this->spend($userId, $coinCost, 'Mua gói lượt tải tài liệu', null);

        $userLog = UserLog::where('user_id', $userId)->firstOrFail();
        $userLog->increment('download_limit', $downloadAmount);

        return $userLog;
    }
}