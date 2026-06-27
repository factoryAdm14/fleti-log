<?php

namespace Modules\FinanceManagement\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\FinanceManagement\Service\DriverPlanService;
use Modules\FinanceManagement\Service\DriverSubscriptionService;
use Modules\FinanceManagement\Service\DriverWithdrawService;
use Modules\FinanceManagement\Service\FinanceDashboardService;
use Modules\FinanceManagement\Service\FinancePixPayoutService;
use Modules\FinanceManagement\Service\FinanceSettingService;
use Modules\FinanceManagement\Service\FinanceWithdrawAdminService;
use Modules\FinanceManagement\Service\FinancialSplitService;
use Modules\FinanceManagement\Service\PaymentGatewayManager;
use Modules\FinanceManagement\Service\Interfaces\DriverPlanServiceInterface;
use Modules\FinanceManagement\Service\Interfaces\DriverSubscriptionServiceInterface;
use Modules\FinanceManagement\Service\Interfaces\DriverWithdrawServiceInterface;
use Modules\FinanceManagement\Service\Interfaces\FinanceDashboardServiceInterface;
use Modules\FinanceManagement\Service\Interfaces\FinanceSettingServiceInterface;
use Modules\FinanceManagement\Service\Interfaces\FinanceWithdrawAdminServiceInterface;
use Modules\FinanceManagement\Service\FinanceAuditLogService;
use Modules\FinanceManagement\Service\FinancePaymentVerificationService;
use Modules\FinanceManagement\Service\FinanceWithdrawSecurityService;
use Modules\FinanceManagement\Service\Interfaces\FinancialSplitServiceInterface;

class FinanceManagementServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'FinanceManagement';

    protected string $moduleNameLower = 'financemanagement';

    public function boot(): void
    {
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);

        $this->app->bind(FinanceSettingServiceInterface::class, FinanceSettingService::class);
        $this->app->bind(FinanceDashboardServiceInterface::class, FinanceDashboardService::class);
        $this->app->bind(FinancialSplitServiceInterface::class, FinancialSplitService::class);
        $this->app->bind(DriverPlanServiceInterface::class, DriverPlanService::class);
        $this->app->bind(DriverSubscriptionServiceInterface::class, DriverSubscriptionService::class);
        $this->app->bind(DriverWithdrawServiceInterface::class, DriverWithdrawService::class);
        $this->app->bind(FinanceWithdrawAdminServiceInterface::class, FinanceWithdrawAdminService::class);
        $this->app->singleton(PaymentGatewayManager::class);
        $this->app->singleton(FinancePixPayoutService::class);
        $this->app->singleton(FinanceWithdrawSecurityService::class);
        $this->app->singleton(FinancePaymentVerificationService::class);
        $this->app->singleton(FinanceAuditLogService::class);
    }

    protected function registerConfig(): void
    {
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'Config/config.php'),
            $this->moduleNameLower
        );
    }

    public function registerViews(): void
    {
        $sourcePath = module_path($this->moduleName, 'Resources/views');
        $this->loadViewsFrom([$sourcePath], $this->moduleNameLower);
    }
}
