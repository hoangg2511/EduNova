<?php

namespace App\Services;

use SePay\SePayClient;
use SePay\Builders\CheckoutBuilder;
use Illuminate\Support\Facades\Log;
class SePayService
{protected $client;

    public function __construct()
    {
        // Khởi tạo client một lần duy nhất
        $this->client = new SePayClient(
            config('services.sepay.merchant_id'),
            config('services.sepay.merchant_secret'),
            config('services.sepay.environment', SePayClient::ENVIRONMENT_PRODUCTION),
            [
                'debug' => true,
                'retry_attempts' => 3,
                'retry_delay' => 1000,
                'user_agent' => 'MyApp/1.0 SePay-PHP-SDK/1.0.0',
                'logger' => Log::getLogger(),
            ]
        );
        $this->client->enableDebugMode();
    }
    public static function getClient()
    {
        // Khởi tạo client với cấu hình từ .env
        $client = new SePayClient(
            config('services.sepay.merchant_id'),
            config('services.sepay.merchant_secret'),
            config('services.sepay.environment', SePayClient::ENVIRONMENT_PRODUCTION),
            [
                'debug' => true,
                'retry_attempts' => 3,
                'retry_delay' => 1000,
                'user_agent' => 'MyApp/1.0 SePay-PHP-SDK/1.0.0',
            ]
        );

        // Kích hoạt debug theo yêu cầu
        $client->enableDebugMode();
        $client->setRetryAttempts(3)->setRetryDelay(1000);

        return $client;
    }
    public function generatePaymentForm($orderId, $amount, $description)
    {
        $checkoutData = CheckoutBuilder::make()
            ->currency('VND')
            ->orderInvoiceNumber($orderId)
            ->orderAmount($amount)
            ->operation('PURCHASE')
            ->orderDescription($description)
            ->successUrl(route('payment.success', ['order' => $orderId]))
            ->errorUrl(route('payment.error', ['order' => $orderId]))
            ->cancelUrl(route('payment.cancel', ['order' => $orderId]))
            ->build();

        // Sử dụng phương thức của SDK để tạo form.
        // SDK đã tự động tính toán chữ ký và đảm bảo đúng thứ tự cho bạn.
        return $this->client->checkout()->generateFormHtml($checkoutData);
    }
}