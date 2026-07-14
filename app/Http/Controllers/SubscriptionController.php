<?php
namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\UserLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SubscriptionController extends Controller
{
    // Trang danh sách gói
    public function index()
    {
        $userId = auth()->id();
        Log::info('SubscriptionController@index: Người dùng xem trang danh sách gói', ['user_id' => $userId]);

        $plans       = Plan::where('is_active', true)->orderBy('price')->get();
        $currentSub  = auth()->user()->activeSubscription();
        $currentPlan = auth()->user()->currentPlan();

        Log::info('SubscriptionController@index: Đã load dữ liệu gói', [
            'user_id'          => $userId,
            'plans_count'      => $plans->count(),
            'current_sub_id'   => $currentSub->id ?? null,
            'current_plan_id'  => $currentPlan->id ?? null,
        ]);

        return view('user.subscriptions.index', compact('plans', 'currentSub', 'currentPlan'));
    }

    public function handleIpn(Request $request)
    {
        $data = $request->json()->all();
        Log::info('IPN Raw Payload Received:', $data);

        if (($data['notification_type'] ?? '') !== 'ORDER_PAID') {
            Log::info('IPN bỏ qua: không phải sự kiện ORDER_PAID', ['type' => $data['notification_type'] ?? null]);
            return response()->json(['success' => true], 200);
        }

        $invoice = $data['order']['order_invoice_number'] ?? '';
        Log::info('IPN: Nhận sự kiện ORDER_PAID', ['invoice' => $invoice]);

        // Định dạng invoice: INV_{userId}_{planId}_{timestamp}
        $parts   = explode('_', $invoice);
        $userId  = $parts[1] ?? null;
        $planId  = $parts[2] ?? null;

        if (!$userId || !$planId) {
            Log::error('IPN: Không parse được userId/planId từ invoice', [
                'invoice' => $invoice,
                'parts'   => $parts,
            ]);
            return response()->json(['success' => false, 'message' => 'Invalid invoice format'], 422);
        }

        Log::info('IPN: Parse invoice thành công', [
            'invoice' => $invoice,
            'user_id' => $userId,
            'plan_id' => $planId,
        ]);

        $user = User::find($userId);
        $plan = Plan::find($planId);

        if (!$user || !$plan) {
            Log::error('IPN: Không tìm thấy user hoặc plan', [
                'user_id' => $userId,
                'plan_id' => $planId,
                'user_found' => (bool) $user,
                'plan_found' => (bool) $plan,
            ]);
            return response()->json(['success' => false, 'message' => 'User or Plan not found'], 404);
        }

        try {
            DB::transaction(function () use ($user, $plan, $invoice) {
                $this->activateSubscriptionForUser($user, $plan, $invoice);
            });

            Log::info("IPN: Đã kích hoạt gói '{$plan->slug}' cho User ID: {$user->id}", [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'invoice' => $invoice,
            ]);

            return response()->json(['success' => true], 200);
        } catch (\Exception $e) {
            Log::error('IPN: Lỗi khi kích hoạt subscription', [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'invoice' => $invoice,
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return response()->json(['success' => false, 'message' => 'Internal error'], 500);
        }
    }

        /**
     * Kích hoạt gói mới cho user sau khi thanh toán thành công:
     * - Hủy các subscription active cũ (chuyển sang 'expired')
     * - Tạo/kích hoạt subscription mới theo plan vừa đăng ký
     * - Đồng bộ lại toàn bộ hạn mức trong UserLog theo plan mới
     */
    private function activateSubscriptionForUser(User $user, Plan $plan, string $invoiceNumber): void
    {
        Log::info('activateSubscriptionForUser: Bắt đầu kích hoạt', [
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'invoice' => $invoiceNumber,
        ]);

        // 1. Hủy các subscription đang active khác (nếu có) để tránh 2 gói active cùng lúc
        $expiredCount = Subscription::where('user_id', $user->id)
            ->where('status', 'active')
            ->update(['status' => 'expired']);

        Log::info('activateSubscriptionForUser: Đã expire subscription cũ', [
            'user_id'       => $user->id,
            'expired_count' => $expiredCount,
        ]);

        // 2. Tính thời hạn gói mới
        $startsAt = now();
        $endsAt   = $plan->duration_days > 0
            ? $startsAt->copy()->addDays($plan->duration_days)
            : null; // null = không giới hạn thời gian (như Free)

        // 3. Tạo subscription mới cho plan vừa đăng ký
        $newSub = Subscription::create([
            'user_id'        => $user->id,
            'plan_id'        => $plan->id,
            'status'         => 'active',
            'starts_at'      => $startsAt,
            'ends_at'        => $endsAt,
            'transaction_id' => $invoiceNumber,
        ]);

        Log::info('activateSubscriptionForUser: Đã tạo subscription mới', [
            'subscription_id' => $newSub->id,
            'user_id'         => $user->id,
            'plan_id'         => $plan->id,
            'starts_at'       => $startsAt->toDateTimeString(),
            'ends_at'         => $endsAt?->toDateTimeString(),
        ]);

        // 4. Đồng bộ lại toàn bộ hạn mức trong UserLog theo plan mới
        $log = UserLog::updateOrCreate(
            ['user_id' => $user->id],
            [
                'token_limit'     => $plan->token_limit,
                'knowledge_limit' => $plan->knowledge_limit,
                'download_limit'  => $plan->download_limit,
                'duration_days'   => $plan->duration_days,
            ]
        );

        Log::info('activateSubscriptionForUser: Đã đồng bộ UserLog', [
            'user_log_id'     => $log->id,
            'user_id'         => $user->id,
            'token_limit'     => $plan->token_limit,
            'knowledge_limit' => $plan->knowledge_limit,
            'download_limit'  => $plan->download_limit,
            'duration_days'   => $plan->duration_days,
        ]);
    }

    // Người dùng chọn gói → redirect sang SePay
    public function subscribe(Request $request)
    {
        $ngrokDomain = config('app.url');

        $successUrl = route('payment.success');
        $errorUrl   = route('payment.error');
        $cancelUrl  = route('payment.cancel');

        $request->validate(['plan_id' => 'required|exists:plans,id']);

        $plan = Plan::findOrFail($request->plan_id);
        $user = auth()->user();

        Log::info('SubscriptionController@subscribe: Người dùng chọn gói', [
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'plan_slug' => $plan->slug,
        ]);

        // Không cho đăng ký lại gói đang dùng
        if ($user->currentPlan()->id === $plan->id) {
            Log::info('SubscriptionController@subscribe: Người dùng cố đăng ký lại gói đang dùng', [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
            ]);
            return back()->with('info', 'Bạn đang dùng gói này rồi!');
        }

        // Gói Free: kích hoạt ngay không cần qua cổng thanh toán
        if ($plan->isFree()) {
            Log::info('SubscriptionController@subscribe: Kích hoạt gói Free trực tiếp', [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
            ]);
            $this->activateSubscriptionForUser($user, $plan, 'FREE_' . $user->id . '_' . time());
            return back()->with('success', 'Đã chuyển về gói Miễn phí!');
        }

        try {
            $invoiceNumber = sprintf('INV_%s_%s_%s', auth()->id(), $plan->id, time());
            $merchantId = config('services.sepay.merchant_id');
            $secretKey  = config('services.sepay.merchant_secret');
            $baseUrl    = config('services.sepay.api_url');

            if (!$baseUrl || !$merchantId || !$secretKey) {
                Log::error('SubscriptionController: SePay cấu hình thiếu', [
                    'baseUrl'      => $baseUrl,
                    'merchantId'   => (bool) $merchantId,
                    'hasSecretKey' => (bool) $secretKey,
                ]);
                return back()->with('error', 'Không xác định được đường dẫn cổng thanh toán hoặc cấu hình merchant.');
            }

            $fields = [
                'order_amount' => (string) $plan->price,
                'merchant' => $merchantId,
                'currency' => 'VND',
                'operation' => 'PURCHASE',
                'order_description' => 'Thanh toán đơn hàng #' . $invoiceNumber,
                'order_invoice_number' => $invoiceNumber,
                'success_url' => str_replace(url('/'), $ngrokDomain, $successUrl),
                'error_url'   => str_replace(url('/'), $ngrokDomain, $errorUrl),
                'cancel_url'  => str_replace(url('/'), $ngrokDomain, $cancelUrl),
            ];
            Log::info('SubscriptionController: Chuẩn bị redirect sang SePay', ['fields' => $fields]);
            $signedFields = [];
            foreach ($fields as $key => $value) {
                $signedFields[] = "$key=$value";
            }

            $signature = base64_encode(hash_hmac('sha256', implode(',', $signedFields), $secretKey, true));
            $fields['signature'] = $signature;

            $actionUrl = rtrim($baseUrl, '/');
            $url = $actionUrl . '?' . http_build_query($fields);
            Log::info('SubscriptionController: Redirecting to SePay checkout (GET)', ['url' => $url]);

            return redirect()->away($url);

        } catch (\Exception $e) {
            Log::error('SubscriptionController: Lỗi trong subscribe', [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'Đã xảy ra lỗi khi tạo form thanh toán.');
        }
    }

    // Trang thanh toán (placeholder)
    public function checkout(Plan $plan)
    {
        Log::info('SubscriptionController@checkout: Người dùng xem trang checkout', [
            'user_id' => auth()->id(),
            'plan_id' => $plan->id,
            'plan_slug' => $plan->slug,
        ]);

        return view('user.subscriptions.checkout', compact('plan'));
    }

    // Webhook kích hoạt subscription (dùng transaction_id, giữ lại cho tương thích cũ)
    public function activate(Request $request)
    {
        $transactionId = $request->input('transaction_id');
        Log::info('SubscriptionController@activate: Nhận yêu cầu kích hoạt qua transaction_id', [
            'transaction_id' => $transactionId,
        ]);

        $sub = Subscription::where('transaction_id', $transactionId)
                           ->where('status', 'pending')
                           ->firstOrFail();

        $sub->update(['status' => 'active']);

        Log::info('SubscriptionController@activate: Đã kích hoạt subscription', [
            'subscription_id' => $sub->id,
            'user_id'         => $sub->user_id,
            'plan_id'         => $sub->plan_id,
            'transaction_id'  => $transactionId,
        ]);

        return response()->json(['success' => true]);
    }
}