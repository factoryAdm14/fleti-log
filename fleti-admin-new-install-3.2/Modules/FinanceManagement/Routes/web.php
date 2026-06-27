<?php

use Illuminate\Support\Facades\Route;
use Modules\FinanceManagement\Http\Controllers\Web\Admin\DriverPlanAdminController;
use Modules\FinanceManagement\Http\Controllers\Web\Admin\DriverSubscriptionAdminController;
use Modules\FinanceManagement\Http\Controllers\Web\Admin\FinanceAuditLogController;
use Modules\FinanceManagement\Http\Controllers\Web\Admin\FinanceDashboardController;
use Modules\FinanceManagement\Http\Controllers\Web\Admin\FinanceSettingsController;
use Modules\FinanceManagement\Http\Controllers\Web\Admin\FinanceWithdrawAdminController;

Route::group(['prefix' => 'admin', 'as' => 'admin.', 'middleware' => ['admin']], function () {
    Route::group(['prefix' => 'finance', 'as' => 'finance.'], function () {
        Route::get('dashboard', [FinanceDashboardController::class, 'index'])->name('dashboard.index');
        Route::get('audit', [FinanceAuditLogController::class, 'index'])->name('audit.index');
        Route::get('settings', [FinanceSettingsController::class, 'index'])->name('settings.index');
        Route::post('settings', [FinanceSettingsController::class, 'update'])->name('settings.update');
        Route::get('withdraws', [FinanceWithdrawAdminController::class, 'index'])->name('withdraws.index');
        Route::post('withdraws/{id}/action', [FinanceWithdrawAdminController::class, 'action'])
            ->middleware('finance.withdraw')
            ->name('withdraws.action');
        Route::post('withdraws/{id}/retry-pix', [FinanceWithdrawAdminController::class, 'retryPix'])
            ->middleware('finance.withdraw')
            ->name('withdraws.retry-pix');

        Route::get('plans', [DriverPlanAdminController::class, 'index'])->name('plans.index');
        Route::get('plans/create', [DriverPlanAdminController::class, 'create'])->name('plans.create');
        Route::post('plans', [DriverPlanAdminController::class, 'store'])->name('plans.store');
        Route::get('plans/{id}/edit', [DriverPlanAdminController::class, 'edit'])->name('plans.edit');
        Route::put('plans/{id}', [DriverPlanAdminController::class, 'update'])->name('plans.update');
        Route::post('plans/{id}/toggle', [DriverPlanAdminController::class, 'toggle'])->name('plans.toggle');

        Route::get('subscriptions', [DriverSubscriptionAdminController::class, 'index'])->name('subscriptions.index');
        Route::get('subscriptions/search-drivers', [DriverSubscriptionAdminController::class, 'searchDrivers'])->name('subscriptions.search-drivers');
        Route::post('subscriptions/activate', [DriverSubscriptionAdminController::class, 'activate'])->name('subscriptions.activate');
        Route::post('subscriptions/{id}/cancel', [DriverSubscriptionAdminController::class, 'cancel'])->name('subscriptions.cancel');
    });
});
