<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php', // Bỏ comment dòng này
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
        'payment/ipn',
        ]);
        $middleware->statefulApi();
        // Áp dụng middleware web cho tất cả routes
        $middleware->web(append: [
            // Thêm middleware tùy chỉnh nếu cần
        
        ]);
        
        // Đăng ký tên viết tắt cho Middleware tự viết
        $middleware->alias([
            'is_admin' => \App\Http\Middleware\IsAdmin::class,
            'is_user' => \App\Http\Middleware\IsUser::class,
            'subscription.pro' => \App\Http\Middleware\RequireSubscription::class,
            'sepay.auth' => \App\Http\Middleware\EnsureSePayAuthenticated::class,
            'check.limit' => \App\Http\Middleware\CheckUsageLimit::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Nơi xử lý lỗi tập trung (bảo trì, lỗi 404, lỗi 500...), hiện tại để mặc định là đủ chạy tốt.
    })->create();