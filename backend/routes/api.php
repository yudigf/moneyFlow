<?php

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\ReportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('reports/summary', [ReportController::class, 'summary']);
Route::apiResource('wallets', WalletController::class);
Route::apiResource('categories', CategoryController::class);
Route::apiResource('transactions', TransactionController::class);
