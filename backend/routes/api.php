<?php

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\TelegramWebhookController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\WalletController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('reports/summary', [ReportController::class, 'summary']);
Route::apiResource('wallets', WalletController::class);
Route::apiResource('categories', CategoryController::class);
Route::apiResource('transactions', TransactionController::class);

// Telegram Bot Webhook — no auth, Telegram pushes to us
// Security is enforced by TELEGRAM_ALLOWED_CHAT_ID in the controller
Route::post('telegram/webhook', [TelegramWebhookController::class, 'handle']);
