<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WebhookService;
use Illuminate\Support\Facades\Log;
class WebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Lấy dữ liệu cực kỳ đơn giản
        $payload = $request->all();

        Log::info('SePay Webhook Received:', $payload);
        return response()->json(['success' => true], 200);
    }
}
