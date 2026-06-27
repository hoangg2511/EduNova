<?php
namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\SePayService;
use SePay\Builders\CheckoutBuilder;
use Illuminate\Support\Facades\Http;

class SubscriptionController extends Controller
{
    // Trang danh sách gói
    public function index()
    {
        $plans              = Plan::where('is_active', true)->orderBy('price')->get();
        $currentSub         = auth()->user()->activeSubscription();
        $currentPlan        = auth()->user()->currentPlan();

        return view('user.subscriptions.index', compact('plans', 'currentSub', 'currentPlan'));
    }

    public function handleIpn(Request $request)
    {
        $data = $request->json()->all();
        Log::info('IPN Raw Payload Received:', $data);

        if (($data['notification_type'] ?? '') === 'ORDER_PAID') {
            $invoice = $data['order']['order_invoice_number'] ?? '';
            
            // GIẢ SỬ: Bạn định dạng order_invoice_number là "USER_{user_id}_INV_{id}"
            // Bạn cần phân tách để lấy ra user_id
            $parts = explode('_', $invoice);
            $userId = $parts[1] ?? null; 

            if ($userId) {
                // Cập nhật subscription
                \App\Models\Subscription::updateOrCreate(
                    ['user_id' => $userId], // Tìm dựa trên user_id
                    [
                        'status'    => 'active', // Hoặc giá trị từ $data['...']
                        'plan_id'   => $data['order']['orderInvoiceNumber'] ?? 'default_plan',
                        'updated_at'=> now(),
                    ]
                );
                
                Log::info("IPN: Đã cập nhật subscription cho User ID: {$userId}");
            }
        }

        return response()->json(['success' => true], 200);
    }

    // Người dùng chọn gói → lưu subscription
    public function subscribe(Request $request)
    {
        Log::info('SubscriptionController@subscribe: Bắt đầu quá trình đăng ký', ['user_id' => auth()->id(), 'plan_id' => $request->plan_id]);
        $request->validate(['plan_id' => 'required|exists:plans,id']);

        $plan = Plan::findOrFail($request->plan_id);
        $user = auth()->user();

        // Không cho đăng ký lại gói đang dùng
        if ($user->currentPlan()->id === $plan->id) {
            return back()->with('info', 'Bạn đang dùng gói này rồi!');
        }
        if ($plan->isFree()) {
            return back()->with('success', 'Đã chuyển về gói Miễn phí!');
        }
        try {
            // 1. Khởi tạo Client
            $sepay = SePayService::getClient();
            Log::debug('PaymentController: SePay Client đã khởi tạo thành công');

            // 2. Build dữ liệu
            $invoiceNumber = $plan->id;
            Log::debug('PaymentController: Đang build checkout data', ['invoice_number' => $invoiceNumber]);

            $checkoutData = CheckoutBuilder::make()
                ->currency('VND')
                ->orderAmount($plan->price)
                ->operation('PURCHASE')
                ->orderDescription('Thanh toán đơn hàng #' . time())
                ->orderInvoiceNumber($invoiceNumber)
                ->successUrl(route('payment.success'))
                ->errorUrl(route('payment.error'))
                ->cancelUrl(route('payment.cancel'))
                ->build();

            Log::debug('PaymentController: Checkout data đã build xong');

            // 3. Tạo form fields
            $formFields = $sepay->checkout()->generateFormFields($checkoutData);
            Log::info('PaymentController: Form fields đã được tạo', ['count' => count($formFields)]);

            // 4. Kiểm tra sự tồn tại của view trước khi trả về
            if (!view()->exists('payment.checkout')) {
                Log::error('PaymentController: File view [payment.checkout] không tồn tại!');
                return "Lỗi: Không tìm thấy file view payment.checkout";
            }
            
            // --- THÊM DÒNG NÀY ĐỂ DEBUG ---
            Log::debug('PaymentController: Danh sách các field sẽ gửi lên SePay:', $formFields);
            // ------------------------------
            
            Log::info('PaymentController: Form fields đã được tạo', ['count' => count($formFields)]);
            $url = config('services.sepay.api_url_sandbox') . '?' . http_build_query($formFields);
            log::info('PaymentController: Redirecting to SePay URL', ['url' => $url]);
            // Return thẳng về URL để trình duyệt tự mở trang thanh toán
            return redirect()->away($url);

        } catch (\Exception $e) {
            Log::error('PaymentController: Lỗi trong showCheckout', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return "Đã xảy ra lỗi: " . $e->getMessage();
        }

        

        // Gói trả phí → redirect đến trang thanh toán (tích hợp Stripe sau)
        // return redirect()->route('user.subscriptions.checkout', $plan->id);
    }

    // Trang thanh toán (placeholder — tích hợp Stripe sau)
    public function checkout(Plan $plan)
    {
        return view('user.subscriptions.checkout', compact('plan'));
    }

    // Webhook từ Stripe gọi vào để kích hoạt subscription (tích hợp sau)
    public function activate(Request $request)
    {
        $transactionId = $request->input('transaction_id');
        $sub = Subscription::where('transaction_id', $transactionId)
                           ->where('status', 'pending')
                           ->firstOrFail();

        $sub->update(['status' => 'active']);

        return response()->json(['success' => true]);
    }
}