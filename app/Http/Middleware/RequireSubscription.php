<?php
// app/Http/Middleware/RequireSubscription.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
class RequireSubscription
{
    // Dùng: ->middleware('subscription:pro')
    public function handle(Request $request, Closure $next, string $feature = 'pro')
    {
        log::info('RequireSubscription Middleware: Checking subscription for feature', ['user_id' => auth()->id(), 'feature' => $feature]);
        if (!auth()->user()->hasFeature($feature)) {
            if ($request->expectsJson()) {
                log::warning('RequireSubscription Middleware: Access denied for API request', ['user_id' => auth()->id(), 'feature' => $feature]);
                return response()->json(['message' => 'Tính năng này yêu cầu gói trả phí.'], 403);
            }
            log::warning('RequireSubscription Middleware: Access denied for web request', ['user_id' => auth()->id(), 'feature' => $feature]);
            return redirect()
                ->route('user.subscriptions')
                ->with('warning', 'Bạn cần nâng cấp gói để dùng tính năng này!');
        }
        log::info('RequireSubscription Middleware: Access granted', ['user_id' => auth()->id(), 'feature' => $feature]);
        return $next($request);
    }
}