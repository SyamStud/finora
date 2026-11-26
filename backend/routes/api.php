<?php

use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\AssetController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BudgetController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\FundController;
use App\Http\Controllers\Api\V1\PendingTransactionController;
use App\Http\Controllers\Api\V1\PocketController;
use App\Http\Controllers\Api\V1\TransactionController;
use App\Http\Controllers\Api\V1\TransferController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);

        Route::apiResource('accounts', AccountController::class);
        Route::get('accounts/{account}/pockets', [PocketController::class, 'index']);
        Route::post('accounts/{account}/pockets', [PocketController::class, 'store']);
        Route::get('accounts/{account}/allocations', [PocketController::class, 'allocations']);

        Route::get('pockets', [PocketController::class, 'all']);
        Route::post('pockets/allocate', [PocketController::class, 'allocate']);
        Route::apiResource('pockets', PocketController::class)->only(['show', 'update', 'destroy']);

        Route::apiResource('categories', CategoryController::class);
        Route::apiResource('transactions', TransactionController::class);
        Route::apiResource('transfers', TransferController::class)->only(['index', 'store', 'show', 'destroy']);
        Route::apiResource('assets', AssetController::class);
        Route::post('assets/{asset}/refresh-price', [FundController::class, 'refreshPrice']);
        Route::post('assets/{asset}/top-up', [AssetController::class, 'topUp']);
        Route::post('assets/{asset}/withdraw', [AssetController::class, 'withdraw']);
        Route::get('assets/{asset}/movements', [AssetController::class, 'movements']);
        Route::get('funds/nav', [FundController::class, 'nav']);
        Route::apiResource('budgets', BudgetController::class);

        Route::get('assets/{asset}/valuations', [AssetController::class, 'valuations']);
        Route::post('assets/{asset}/valuations', [AssetController::class, 'recordValuation']);

        Route::get('dashboard', [DashboardController::class, 'index']);

        Route::post('notifications/ingest', [PendingTransactionController::class, 'ingest']);
        Route::get('pending-transactions', [PendingTransactionController::class, 'index']);
        Route::patch('pending-transactions/{pending}', [PendingTransactionController::class, 'update']);
        Route::post('pending-transactions/{pending}/approve', [PendingTransactionController::class, 'approve']);
        Route::post('pending-transactions/{pending}/reject', [PendingTransactionController::class, 'reject']);
    });
});
