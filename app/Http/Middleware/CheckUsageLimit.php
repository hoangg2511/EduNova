<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\UserLog; // Hoặc bảng lưu lượt dùng của bạn
use App\Models\Subscription; // Model User nếu cần
class CheckUsageLimit
{
        public function handle(Request $request, Closure $next, string $feature = 'generate')
    {
        $user = $request->user();
        $userLog = UserLog::where('user_id', $user->id)->first();
        $subscription = Subscription::where('user_id', $user->id)->first();
        
        // Fallback nếu không có subscription
        $resetDate = $subscription ? $subscription->ends_at : 'cuối kỳ';

        // Chỉ kiểm tra dựa trên tham số $feature truyền vào route
        switch ($feature) {
            case 'token':
                if (!$userLog || $userLog->token_limit <= 0) {
                    return response()->json(['message' => "Hết lượt token. Reset vào: $resetDate"], 403);
                }
                break;
                
            case 'knowledge':
                if (!$userLog || $userLog->knowledge_limit <= 0) {
                    return response()->json(['message' => "Hết lượt tạo cây kiến thức. Reset vào: $resetDate"], 403);
                }
                break;

            case 'download':
                if (!$userLog || $userLog->download_limit <= 0) {
                    return response()->json(['message' => "Hết lượt tải xuống. Reset vào: $resetDate"], 403);
                }
                break;
        }

        return $next($request);
    }
}