<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ExamTakerController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SubscriptionController;
// 1. PUBLIC ROUTES (Ai cũng vào được, kể cả người đã login hay chưa)
Route::get('/', function () {
    if (auth()->check()) {
        return auth()->user()->isAdmin() 
            ? redirect()->route('admin.dashboard') 
            : redirect()->route('user.dashboard');
    }
    return redirect()->route('login');
});
Route::get('/debug-bom', function () {
        ob_start();
        // giả lập gọi hàm templateExam nhưng chỉ lấy vài chục byte đầu
        $service = app(\App\Services\ExcelService::class);
        $response = $service->templateExam();
        $content = '';
        ob_start();
        $response->sendContent();
        $content = ob_get_clean();
        return response(bin2hex(substr($content, 0, 20)))->header('Content-Type', 'text/plain');
    });

Route::get('/exams/taker/{id}', [ExamTakerController::class, 'show'])->name('exams.taker');
Route::post('/exams/taker/{id}/submit', [ExamTakerController::class, 'submit'])->name('exams.taker.submit');

Route::get('/checkout', [PaymentController::class, 'showCheckout'])->name('payment.checkout')->middleware('auth');
Route::get('/payment/success', [PaymentController::class, 'success'])->name('payment.success');
Route::get('/payment/error', [PaymentController::class, 'error'])->name('payment.error');
Route::get('/payment/cancel', [PaymentController::class, 'cancel'])->name('payment.cancel');
Route::post('/payment/ipn', [SubscriptionController::class, 'handleIpn'])->name('payment.ipn');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.store');
    Route::get('/register', [LoginController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [LoginController::class, 'register'])->name('register.store');
    Route::post('/register/google', [UserController::class, 'registerGoogle'])->name('register.google');
    // Route::get('/auth/google', [LoginController::class, 'register'])->name('auth.google');
    Route::post('/logout', [UserController::class, 'logout'])->name('logout');
});


Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/dashboard', function () {
        return auth()->user()->isAdmin() 
            ? redirect()->route('admin.dashboard') 
            : redirect()->route('user.dashboard');
    })->name('dashboard');
});

// 4. LOAD ROUTE FILES
includeRouteFiles(__DIR__ . '/user');
includeRouteFiles(__DIR__ . '/admin');