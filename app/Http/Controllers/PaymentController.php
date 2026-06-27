<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Services\SePayService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use SePay\Builders\CheckoutBuilder;
use SePay\Exceptions\AuthenticationException;
use SePay\Exceptions\ValidationException;
use SePay\Exceptions\NotFoundException;
use SePay\Exceptions\RateLimitException;
use SePay\Exceptions\ServerException;

class PaymentController extends Controller
{


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

    public function showCheckout()
    {
        Log::info('PaymentController: Bắt đầu showCheckout');

        try {
            // 1. Khởi tạo Client
            $sepay = SePayService::getClient();
            Log::debug('PaymentController: SePay Client đã khởi tạo thành công');

            // 2. Build dữ liệu
            $invoiceNumber = 'INV_' . time();
            Log::debug('PaymentController: Đang build checkout data', ['invoice_number' => $invoiceNumber]);

            $checkoutData = CheckoutBuilder::make()
                ->currency('VND')
                ->orderAmount(100000)
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
            return view('payment.checkout', compact('formFields'));

        } catch (\Exception $e) {
            Log::error('PaymentController: Lỗi trong showCheckout', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return "Đã xảy ra lỗi: " . $e->getMessage();
        }
    }

    public function showOrderDetail($invoiceNumber)
    {
        try {
            $sepay = SePayService::getClient();
            $order = $sepay->orders()->retrieve($invoiceNumber);
            
            return view('payment.detail', compact('order'));

        } catch (AuthenticationException $e) {
            Log::error('SePay Auth Error: ' . $e->getMessage());
            return back()->with('error', 'Lỗi xác thực với cổng thanh toán.');
            
        } catch (ValidationException $e) {
            Log::warning('SePay Validation Error: ' . $e->getMessage());
            return back()->withErrors($e->getFieldErrors());
            
        } catch (NotFoundException $e) {
            Log::warning('SePay Order Not Found: ' . $invoiceNumber);
            return back()->with('error', 'Không tìm thấy đơn hàng này.');
            
        } catch (RateLimitException $e) {
            Log::error('SePay Rate Limit: ' . $e->getRetryAfter());
            return back()->with('error', 'Quá nhiều yêu cầu, vui lòng thử lại sau ' . $e->getRetryAfter() . ' giây.');
            
        } catch (ServerException $e) {
            Log::critical('SePay Server Error: ' . $e->getMessage());
            return back()->with('error', 'Hệ thống thanh toán đang gặp sự cố, vui lòng quay lại sau.');
            
        } catch (\Exception $e) {
            // Bắt các lỗi không xác định khác
            Log::error('Unexpected Payment Error: ' . $e->getMessage());
            return back()->with('error', 'Đã có lỗi xảy ra.');
        }
    }


    public function success(Request $request)
    {
        // Lấy ID đơn hàng từ query string, ví dụ: /payment/success?order=DH123
        $orderId = $request->query('order');
            
        // Kiểm tra trong DB xem đơn hàng $orderId đã thực sự PAID chưa
        // Nếu chưa PAID, có thể redirect về trang kiểm tra hoặc báo lỗi
        return view('payment.success', compact('orderId'));
    }

    public function error(Request $request)
    {
        // Lấy orderId từ query string để thông báo cho người dùng
        $orderId = $request->query('order');

        // Logic bổ sung: Có thể log lỗi vào hệ thống để theo dõi
        if ($orderId) {
            \Log::warning("Payment failed for order: $orderId");
        }

        return view('payment.error', compact('orderId'));
    }

    public function cancel(Request $request)
    {
        // Người dùng chủ động hủy thanh toán
        $orderId = $request->query('order');

        return view('payment.cancel', compact('orderId'));
    }

}
