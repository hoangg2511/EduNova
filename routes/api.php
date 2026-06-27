<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KnowledgeController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\WebhookController;

// 1. WEBHOOK: URL thực tế sẽ là: /api/sepay/webhook
// Đặt ngoài middleware auth để SePay có thể gửi dữ liệu tự do
Route::middleware(['sepay.auth'])->post('/sepay/webhook', [WebhookController::class, 'handle']);

// 2. NHÓM API CẦN ĐĂNG NHẬP (Session/Web Guard)
// URL sẽ là: /api/knowledge/...
Route::middleware('auth')->group(function () {
    Route::post('/knowledge/start',  [KnowledgeController::class, 'start']);
    Route::post('/knowledge/answer', [KnowledgeController::class, 'answer']);
    Route::post('/knowledge/store',  [KnowledgeController::class, 'store']);
});

// 3. NHÓM API CẦN SANCTUM + RATE LIMIT (Knowledge)
// URL sẽ là: /api/knowledge/generate
Route::middleware(['auth:sanctum', 'check.limit:knowledge'])->group(function () {
    Route::post('/knowledge/generate', [KnowledgeController::class, 'generate'])->name('knowledge.generate');
});

// 4. NHÓM API CẦN SANCTUM + RATE LIMIT (Chatbot)
// URL sẽ là: /api/chatbot/...
Route::middleware(['auth:sanctum', 'check.limit:token'])->group(function () {
    Route::post('/chatbot',             [ChatbotController::class, 'chat']);
    Route::post('/chatbot/stream',      [ChatbotController::class, 'stream']);
    Route::get('/chatbot/history',      [ChatbotController::class, 'history']);
    Route::post('/chatbot/clear',       [ChatbotController::class, 'clear']);
    Route::post('/chatbot/flashcard',   [ChatbotController::class, 'createFlashcard']);
});

// 5. DOCUMENT ROUTES (Optional - uncomment when needed)
// Route::middleware('auth')->group(function () {
//     Route::post('/documents/upload', [DocumentController::class, 'upload'])->name('documents.upload');
//     Route::post('/documents/save', [DocumentController::class, 'saveDocument'])->name('documents.save');
//     Route::delete('/documents/unsave/{id}', [DocumentController::class, 'unsaveDocument'])->name('documents.unsave');
// });