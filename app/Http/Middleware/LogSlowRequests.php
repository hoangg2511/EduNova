<?php
// app/Http/Middleware/LogSlowRequests.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;

class LogSlowRequests
{
    public function handle($request, Closure $next)
    {
        $start = microtime(true);
        Log::info('REQUEST START', [
            'path'   => $request->path(),
            'method' => $request->method(),
            'pid'    => getmypid(),
        ]);

        $response = $next($request);

        $duration = round((microtime(true) - $start) * 1000);
        Log::info('REQUEST END', [
            'path'     => $request->path(),
            'duration_ms' => $duration,
            'pid'      => getmypid(),
        ]);

        return $response;
    }
}