<?php

use Illuminate\Support\Facades\Route;
use Modules\FinanceManagement\Http\Controllers\Api\Driver\FinancePlanController;
use Modules\FinanceManagement\Http\Controllers\Api\Driver\FinanceWalletController;
use Modules\FinanceManagement\Http\Controllers\Api\Driver\FinanceWithdrawController;
use Modules\FinanceManagement\Http\Controllers\Api\PaymentGatewayConfigController;
use Modules\FinanceManagement\Http\Controllers\Webhook\PaymentWebhookController;

Route::post('finance/webhooks/pix/{gateway?}', [PaymentWebhookController::class, 'pix'])
    ->name('finance.webhooks.pix');

Route::get('finance/payment-gateways', [PaymentGatewayConfigController::class, 'available'])
    ->name('finance.payment-gateways');

Route::group(['prefix' => 'driver', 'middleware' => ['auth:api', 'maintenance_mode']], function () {
    Route::get('finance/wallet', [FinanceWalletController::class, 'show']);
    Route::get('finance/wallet/transactions', [FinanceWalletController::class, 'transactions']);

    Route::post('finance/withdraw/request', [FinanceWithdrawController::class, 'request']);
    Route::get('finance/withdraw/pending', [FinanceWithdrawController::class, 'pending']);
    Route::get('finance/withdraw/settled', [FinanceWithdrawController::class, 'settled']);

    Route::get('finance/plans', [FinancePlanController::class, 'index']);
    Route::post('finance/plans/{planId}/checkout', [FinancePlanController::class, 'checkout']);
    Route::get('finance/subscription', [FinancePlanController::class, 'subscription']);
    Route::get('finance/subscription/pending', [FinancePlanController::class, 'pendingSubscription']);
});
