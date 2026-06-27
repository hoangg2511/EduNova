<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KnowledgeController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\FlashCardController;
use App\Http\Controllers\CardController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\NotificationController;
Route::middleware(['auth', 'is_user'])->prefix('user')->name('user.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    

    // Knowledge routes
    Route::get('/knowledge', [KnowledgeController::class, 'index'])->name('knowledge');
    Route::get('/knowledge/roadmap', [KnowledgeController::class, 'roadmap'])->name('knowledge.roadmap');
    Route::post('/knowledge', [KnowledgeController::class, 'store'])->name('knowledge.store');
    Route::get('/knowledge/{id}', [KnowledgeController::class, 'show'])->name('knowledge.show');
    
    
    
    // Document routes
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents');
    Route::post('/documents/upload', [DocumentController::class, 'upload'])->name('documents.upload');
    Route::post('/documents/save', [DocumentController::class, 'saveDocument'])->name('documents.saveDocument');
    Route::delete('/documents/unsave/{id}', [DocumentController::class, 'unsaveDocument'])->name('documents.unsave');
    Route::get('/documents/{id}/view', [DocumentController::class, 'viewPage'])->name('documents.view')->middleware('auth');
    // routes/web.php
    Route::get('/documents/{document}/stream', [DocumentController::class, 'stream'])
        ->name('documents.stream')
        ->middleware('auth');

    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    // Calendar & Subscription routes
    Route::get('/calendars', [CalendarController::class, 'index'])->name('calendars');
    Route::post('/calendars', [CalendarController::class, 'store'])->name('calendars.store');
    Route::put('/calendars/{id}', [CalendarController::class, 'update'])->name('calendars.update');
    Route::delete('/calendars/{id}', [CalendarController::class, 'destroy'])->name('calendars.destroy');

    
    // Exam routes
    Route::get('/exams', [ExamController::class, 'index'])->name('exams');
    Route::get('/exams/export-template-question', [ExamController::class, 'exportTemplateQuestion'])->name('exams.export-template-question');
    Route::get('/exams/export-template-exam', [ExamController::class, 'exportTemplateExam'])->name('exams.export-template-exam');
    Route::post('/exams/import', [ExamController::class, 'importExam'])->name('exams.import');
    Route::get('/exams/{id}/export', [ExamController::class, 'exportExam'])->name('exams.export');
    Route::get('/exams/{exam}', [ExamController::class, 'show'])->name('exams.show');
    Route::post('/exams/upload', [ExamController::class, 'store'])->name('exams.store');
    Route::delete('/exams/{id}', [ExamController::class, 'destroy'])->name('exams.destroy');
    Route::put('/exams/{id}', [ExamController::class, 'update'])->name('exams.update');

    Route::get('/exams/generate-key',      [ExamController::class, 'generateKey'])->name('exams.generate-key');
    Route::get('/exams/{exam}/share-info', [ExamController::class, 'shareInfo'])->name('exams.share-info');
    Route::post('/exams/{exam}/attempts', [ExamController::class, 'storeAttempt'])->name('exams.attempts.store');
    
    // Flashcard routes
    Route::get('/flashcards',          [FlashcardController::class, 'index'])->name('flashcards');
    Route::post('/flashcards',         [FlashcardController::class, 'store'])->name('flashcards.store');
    Route::put('/flashcards/{id}',     [FlashcardController::class, 'update'])->name('flashcards.update');
    Route::delete('/flashcards/{id}',  [FlashcardController::class, 'destroy'])->name('flashcards.destroy');
    Route::delete('/cards/{id}',       [FlashcardController::class, 'destroyCard'])->name('cards.destroy');
    Route::post('/decks/{deckId}/cards', [FlashcardController::class, 'storeCard'])->name('cards.store');
    Route::patch('/cards/{id}/status', [FlashcardController::class, 'updateStatus']);


    Route::get('/streak', [UserController::class, 'getStreak'])->name('user.streak');
     Route::get('/profile', function () {
        return view('user.profile.index');
    })->name('profile');
    Route::prefix('news')->name('news.')->group(function () {
 
    Route::get('/',           [NewsController::class, 'index'])->name('index');
    Route::get('/feed',       [NewsController::class, 'feed'])->name('feed');   // AJAX
    Route::get('/{slug}',     [NewsController::class, 'show'])->name('show');
 
    // bookmark yêu cầu đăng nhập
    Route::post('/{article}/bookmark', [NewsController::class, 'bookmark'])
         ->name('bookmark')
         ->middleware('auth');
});
    Route::get('/subscriptions', function () {return view('user.subscriptions.index');})->name('subscriptions');
    Route::post('/subscriptions/subscribe',[SubscriptionController::class, 'subscribe'])->name('subscriptions.subscribe');
    Route::get('/subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions');
    Route::middleware(['subscription.pro'])->group(function () {

        Route::get('/subscriptions/checkout/{plan}', [SubscriptionController::class, 'checkout'])->name('subscriptions.checkout');
    });


Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
 
// Trang xem tất cả thông báo (route('user.notifications.index') dùng trong blade)
// Route::get('/notifications/list', [NotificationController::class, 'pageIndex'])->name('notifications.index');

//    Route::post('/sepay/init-checkout', [SubscriptionController::class, 'initPayment'])->name('subscriptions.initPayment');
//     Route::get('/sepay/order-detail/{order_id}', [SubscriptionController::class, 'orderDetail'])->name('subscriptions.orderDetail');
//     Route::get('/sepay/orders', [SubscriptionController::class, 'listOrders'])->name('subscriptions.listOrders');
//     Route::post('/sepay/cancel-order', [SubscriptionController::class, 'cancelOrder'])->name('subscriptions.cancelOrder');
});

