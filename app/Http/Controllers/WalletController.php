<?php

namespace App\Http\Controllers;

use App\Models\UserLog;
use App\Models\WalletConfig;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class WalletController extends Controller
{
    public function __construct(private WalletService $walletService) {}

    /**
     * GET /api/wallet/balance
     * Trả về số dư coin hiện tại của user (dùng cho topbar, chatbot, trang tài liệu...).
     */
    public function balance(): JsonResponse
    {
        $balance = $this->walletService->getBalance(auth()->id());

        return response()->json([
            'success' => true,
            'balance' => $balance,
        ]);
    }

    /**
     * GET /api/wallet/purchase-options
     * Trả về tỷ lệ quy đổi hiện tại để FE hiển thị giá trước khi mua.
     */
    public function purchaseOptions(): JsonResponse
    {
        return response()->json([
            'success'  => true,
            'balance'  => $this->walletService->getBalance(auth()->id()),
            'token'    => [
                'coin_cost' => WalletConfig::get('conversion.token_pack_coin_cost', 20),
                'amount'    => WalletConfig::get('conversion.token_pack_amount', 500),
            ],
            'download' => [
                'coin_cost' => WalletConfig::get('conversion.download_pack_coin_cost', 15),
                'amount'    => WalletConfig::get('conversion.download_pack_amount', 5),
            ],
        ]);
    }

    /**
     * POST /api/wallet/buy-token
     * Dùng coin mua thêm token chat (cộng vào UserLog.token_limit).
     */
    public function buyToken(Request $request)
{
    $validated = $request->validate([
        'quantity' => 'nullable|integer|min:1|max:20',
    ]);
    $quantity = $validated['quantity'] ?? 1;

    // Giả sử bạn có hằng số / config cho đơn giá, ví dụ:
    $unitCost   = config('wallet.token_coin_cost', 100);   // coin / gói
    $unitAmount = config('wallet.token_amount', 1000);     // token / gói

    $totalCost   = $unitCost * $quantity;
    $totalAmount = $unitAmount * $quantity;

    $wallet = auth()->user()->wallet;

    if (!$wallet || $wallet->balance < $totalCost) {
        return response()->json(['success' => false, 'message' => 'Số dư Coin không đủ.'], 422);
    }

    DB::transaction(function () use ($wallet, $totalCost, $totalAmount) {
        // Trừ coin — dùng WalletService nếu bạn đã có (đúng như context bạn từng đề cập)
        app(\App\Services\WalletService::class)->debit(
            $wallet,
            $totalCost,
            'Đổi Coin lấy Token chat'
        );

        // Cộng token vào UserLog
        $userLog = \App\Models\UserLog::firstOrCreate(['user_id' => auth()->id()]);
        $userLog->increment('token_limit', $totalAmount);
    });

    return response()->json([
        'success' => true,
        'message' => "Đã đổi thành công {$totalAmount} Token!",
        'balance' => $wallet->fresh()->balance,
    ]);
}

    /**
     * POST /api/wallet/buy-download
     * Dùng coin mua thêm lượt tải tài liệu (cộng vào UserLog.download_limit).
     */
    public function buyDownload(): JsonResponse
    {
        $userId   = auth()->id();
        $coinCost = WalletConfig::get('conversion.download_pack_coin_cost', 15);
        $amount   = WalletConfig::get('conversion.download_pack_amount', 5);

        try {
            $this->walletService->spend(
                $userId,
                $coinCost,
                "Mua gói {$amount} lượt tải tài liệu",
                null
            );
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Không đủ coin để mua gói lượt tải này.',
            ], 422);
        }

        $userLog = UserLog::where('user_id', $userId)->first();
        if ($userLog) {
            $userLog->increment('download_limit', $amount);
            $userLog->refresh();
        }

        Log::info('User bought download pack with coin', [
            'user_id' => $userId, 'coin_cost' => $coinCost, 'download_amount' => $amount,
        ]);

        return response()->json([
            'success'        => true,
            'message'        => "Đã đổi {$coinCost} coin lấy {$amount} lượt tải!",
            'balance'        => $this->walletService->getBalance($userId),
            'download_limit' => $userLog?->download_limit ?? 0,
        ]);
    }
}