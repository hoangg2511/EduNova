<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\admin\DashboardController;
use App\Http\Controllers\admin\DocumentsController;
use App\Http\Controllers\admin\UsersController;
use App\Http\Controllers\admin\NewsController;
use App\Http\Controllers\admin\SubscriptionController;
use App\Http\Controllers\admin\NotificationController;
use App\Http\Controllers\admin\WalletConfigController;
Route::middleware(['auth', 'is_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');


      // Page
    Route::get('/documents', [DocumentsController::class, 'index'])->name('documents.index');
    //  Route::get('/documents', function () {
    //     return view('admin.documents.index');
    // })->name('documents.index');
 
    // JSON APIs (đặt TRƯỚC route có {document})
    Route::get('/documents/pending',        [DocumentsController::class, 'pending'])->name('documents.pending');
    Route::get('/documents/data',           [DocumentsController::class, 'data'])->name('documents.data');
    Route::get('/documents/stats',          [DocumentsController::class, 'stats'])->name('documents.stats');
 
    // Per-document actions
    Route::patch('/documents/{document}/approve',       [DocumentsController::class, 'approve'])->name('documents.approve');
    Route::patch('/documents/{document}/reject',        [DocumentsController::class, 'reject'])->name('documents.reject');
    Route::patch('/documents/{document}/ai-review',     [DocumentsController::class, 'aiReview'])->name('documents.aiReview');
    Route::post('/documents/{document}/view',           [DocumentsController::class, 'incrementView'])->name('documents.view');
    Route::get('/documents/{document}/file',            [DocumentsController::class, 'viewFile'])->name('documents.viewFile'); 
    Route::delete('/documents/{document}',              [DocumentsController::class, 'destroy'])->name('documents.destroy');
    Route::patch('/documents/{document}/toggle-visibility', [DocumentsController::class, 'toggleVisibility'])->name('documents.toggleVisibility');

    
    Route::get('/users', [UsersController::class, 'index'])->name('users.index');
    Route::get('/users/data', [UsersController::class, 'data'])->name('users.data');
    Route::get('/users/{user}', [UsersController::class, 'show'])->name('users.show');
    Route::post('/users/bulk-ban',    [UsersController::class, 'bulkBan'])->name('users.bulkBan');
    Route::post('/users/bulk-delete', [UsersController::class, 'bulkDelete'])->name('users.bulkDelete');
    Route::post('/users',                        [UsersController::class, 'store'])->name('users.store');
    Route::put('/users/{user}',                  [UsersController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}',               [UsersController::class, 'destroy'])->name('users.destroy');
    Route::patch('/users/{user}/toggle-status',  [UsersController::class, 'toggleStatus'])->name('users.toggleStatus');



    Route::get('/news', [NewsController::class, 'index'])->name('news.index');
    Route::get('/news/data', [NewsController::class, 'data'])->name('news.data');
    Route::post('/news/upload-image', [NewsController::class, 'uploadImage'])->name('news.uploadImage');
    Route::post('/news',                           [NewsController::class, 'store'])->name('news.store');
    Route::put('/news/{article}',                  [NewsController::class, 'update'])->name('news.update');
    Route::delete('/news/{article}',               [NewsController::class, 'destroy'])->name('news.destroy');
    Route::patch('/news/{article}/pin',            [NewsController::class, 'togglePin'])->name('news.pin');
    Route::patch('/news/{article}/toggle-status',  [NewsController::class, 'toggleStatus'])->name('news.toggleStatus');
    Route::post('/news/{article}/view',            [NewsController::class, 'incrementView'])->name('news.view');



    Route::get('/subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions.index');
    Route::get('/subscriptions/data', [SubscriptionController::class, 'data'])->name('subscriptions.data');
    Route::post('/subscriptions/plans',                       [SubscriptionController::class, 'storePlan'])->name('subscriptions.plans.store');
    Route::put('/subscriptions/plans/{plan}',                 [SubscriptionController::class, 'updatePlan'])->name('subscriptions.plans.update');
    Route::patch('/subscriptions/plans/{plan}/toggle-active', [SubscriptionController::class, 'togglePlanActive'])->name('subscriptions.plans.toggleActive');
    Route::post('/subscriptions/grant',                       [SubscriptionController::class, 'grant'])->name('subscriptions.grant');
    Route::patch('/subscriptions/{subscription}/cancel',      [SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');


    
    Route::get('/notifications',                                     [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/data',                                [NotificationController::class, 'data'])->name('notifications.data');
    Route::post('/notifications/broadcast',                          [NotificationController::class, 'broadcast'])->name('notifications.broadcast');
    Route::post('/notifications/mark-all-read',                      [NotificationController::class, 'markAllRead'])->name('notifications.markAllRead');
    Route::post('/notifications/bulk-delete',                        [NotificationController::class, 'bulkDelete'])->name('notifications.bulkDelete');
    Route::patch('/notifications/{notification}/read',               [NotificationController::class, 'markRead'])->name('notifications.markRead');
    Route::delete('/notifications/{notification}',                   [NotificationController::class, 'destroy'])->name('notifications.destroy');


    Route::get('/wallet-configs', [WalletConfigController::class, 'index'])->name('wallet-configs.index'); // trang giao diện
    Route::get('/wallet-configs/data', [WalletConfigController::class, 'data'])->name('wallet-configs.data');
    Route::post('/wallet-configs', [WalletConfigController::class, 'store'])->name('wallet-configs.store');
    Route::patch('/wallet-configs/{walletConfig}', [WalletConfigController::class, 'update'])->name('wallet-configs.update');
    Route::delete('/wallet-configs/{walletConfig}', [WalletConfigController::class, 'destroy'])->name('wallet-configs.destroy');
});
