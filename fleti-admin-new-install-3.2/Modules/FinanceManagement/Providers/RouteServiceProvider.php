<?php

namespace Modules\FinanceManagement\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public function map(): void
    {
        $this->mapApiRoutes();
        $this->mapWebRoutes();
    }

    protected function mapWebRoutes(): void
    {
        Route::middleware('web')
            ->group(module_path('FinanceManagement', '/Routes/web.php'));
    }

    protected function mapApiRoutes(): void
    {
        Route::prefix('api')
            ->middleware('api')
            ->group(module_path('FinanceManagement', '/Routes/api.php'));
    }
}
