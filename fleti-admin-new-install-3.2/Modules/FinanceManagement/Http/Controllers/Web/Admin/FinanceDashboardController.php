<?php

namespace Modules\FinanceManagement\Http\Controllers\Web\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\FinanceManagement\Service\Interfaces\FinanceDashboardServiceInterface;

class FinanceDashboardController extends Controller
{
    public function __construct(
        private readonly FinanceDashboardServiceInterface $dashboardService,
    ) {
    }

    public function index(Request $request): View
    {
        $period = $request->get('period', 'all');
        $allowedPeriods = ['all', 'today', 'week', 'month', 'year'];

        if (!in_array($period, $allowedPeriods, true)) {
            $period = 'all';
        }

        $stats = $this->dashboardService->getStats($period);

        return view('financemanagement::admin.dashboard.index', [
            'stats' => $stats,
            'period' => $period,
            'periodOptions' => [
                'all' => 'Todo o período',
                'today' => 'Hoje',
                'week' => 'Últimos 7 dias',
                'month' => 'Este mês',
                'year' => 'Este ano',
            ],
        ]);
    }
}
