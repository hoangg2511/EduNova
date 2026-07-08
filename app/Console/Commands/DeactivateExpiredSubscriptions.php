<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Subscription;
use App\Models\Plan;
use App\Models\UserLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class DeactivateExpiredSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:deactivate-expired-subscriptions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Chuyển các subscription đã hết hạn về gói Free, đồng bộ lại UserLog theo hạn mức Free';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $freePlan = Plan::where('slug', 'free')->first();

        if (!$freePlan) {
            Log::error('DeactivateExpiredSubscriptions: Không tìm thấy gói Free (slug=free), dừng command.');
            $this->error('Không tìm thấy gói Free, kiểm tra lại dữ liệu bảng plans.');
            return self::FAILURE;
        }

        // Chỉ lấy các subscription ĐANG ACTIVE và đã hết hạn, khác gói Free.
        // Không được bỏ điều kiện status='active', nếu không sẽ ghi đè cả
        // các bản ghi lịch sử đã expired từ trước mỗi lần command này chạy lại.
        $expiredSubs = Subscription::where('status', 'active')
            ->where('plan_id', '!=', $freePlan->id)
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', now())
            ->get();

        if ($expiredSubs->isEmpty()) {
            Log::info('DeactivateExpiredSubscriptions: Không có subscription nào hết hạn cần xử lý.');
            $this->info('Không có subscription nào cần xử lý.');
            return self::SUCCESS;
        }

        $successCount = 0;
        $failCount    = 0;

        foreach ($expiredSubs as $sub) {
            try {
                DB::transaction(function () use ($sub, $freePlan) {
                    // 1. Đánh dấu subscription cũ đã hết hạn — KHÔNG sửa plan_id tại chỗ,
                    //    để giữ nguyên lịch sử (gói gì, hết hạn khi nào, transaction_id nào).
                    $sub->update(['status' => 'expired']);

                    // 2. Tạo subscription Free mới, đúng pattern dùng chung với
                    //    SubscriptionController::activateSubscriptionForUser().
                    Subscription::create([
                        'user_id'        => $sub->user_id,
                        'plan_id'        => $freePlan->id,
                        'status'         => 'active',
                        'starts_at'      => now(),
                        'ends_at'        => null, // Free không giới hạn thời gian
                        'payment_method' => 'system',
                        'transaction_id' => 'AUTO_EXPIRE_' . $sub->user_id . '_' . time(),
                    ]);

                    // 3. Đồng bộ lại UserLog theo đúng hạn mức Free — bắt buộc,
                    //    nếu không user vẫn được dùng hạn mức của gói cũ.
                    UserLog::updateOrCreate(
                        ['user_id' => $sub->user_id],
                        [
                            'token_limit'     => $freePlan->token_limit,
                            'knowledge_limit' => $freePlan->knowledge_limit,
                            'download_limit'  => $freePlan->download_limit,
                            'duration_days'   => $freePlan->duration_days,
                        ]
                    );
                });

                Log::info("DeactivateExpiredSubscriptions: User {$sub->user_id} đã bị chuyển về gói Free do hết hạn (sub cũ #{$sub->id}, plan_id cũ {$sub->plan_id}).");
                $successCount++;

            } catch (\Exception $e) {
                Log::error("DeactivateExpiredSubscriptions: Lỗi khi xử lý user {$sub->user_id}", [
                    'subscription_id' => $sub->id,
                    'message'         => $e->getMessage(),
                ]);
                $failCount++;
                // Không throw lại — tiếp tục xử lý các user còn lại.
            }
        }

        Log::info("DeactivateExpiredSubscriptions: Hoàn tất. Thành công: {$successCount}, Lỗi: {$failCount}.");
        $this->info("Hoàn tất. Thành công: {$successCount}, Lỗi: {$failCount}.");

        return self::SUCCESS;
    }
}