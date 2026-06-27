<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSePayAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $signature = $request->header('X-SePay-Signature') ?? '';
        $timestamp = $request->header('X-SePay-Timestamp') ?? ''; // Giữ là string
        $body = $request->getContent(); 
        $secret = config('services.sepay.webhook_secret');
        $stringToHash = $timestamp . '.' . $body;
        $expected = 'sha256=' . hash_hmac('sha256', $stringToHash, $secret);

        if (!hash_equals($expected, $signature)) {
            return response()->json(['success' => false, 'message' => 'Invalid signature'], 401);
        }

        return $next($request);
    }
}
