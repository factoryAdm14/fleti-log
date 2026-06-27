<?php

namespace Modules\FinanceManagement\Http\Controllers\Web\Admin;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Modules\FinanceManagement\Http\Requests\DriverPlanRequest;
use Modules\FinanceManagement\Service\FinanceAuditService;
use Modules\FinanceManagement\Service\Interfaces\DriverPlanServiceInterface;

class DriverPlanAdminController extends Controller
{
    public function __construct(
        private readonly DriverPlanServiceInterface $driverPlanService,
        private readonly FinanceAuditService $financeAuditService,
    ) {
    }

    public function index(): View
    {
        $plans = $this->driverPlanService->paginate(paginationLimit());

        return view('financemanagement::admin.plans.index', compact('plans'));
    }

    public function create(): View
    {
        return view('financemanagement::admin.plans.form', ['plan' => null]);
    }

    public function store(DriverPlanRequest $request): RedirectResponse
    {
        $plan = $this->driverPlanService->create($request->validated());

        $this->financeAuditService->log(
            action: 'driver_plan_created',
            entityType: get_class($plan),
            entityId: $plan->id,
            after: $plan->toArray(),
        );

        Toastr::success('Plano criado com sucesso.');

        return redirect()->route('admin.finance.plans.index');
    }

    public function edit(string $id): View
    {
        $plan = $this->driverPlanService->find($id);

        return view('financemanagement::admin.plans.form', compact('plan'));
    }

    public function update(string $id, DriverPlanRequest $request): RedirectResponse
    {
        $before = $this->driverPlanService->find($id)->toArray();
        $plan = $this->driverPlanService->update($id, $request->validated());

        $this->financeAuditService->log(
            action: 'driver_plan_updated',
            entityType: get_class($plan),
            entityId: $plan->id,
            before: $before,
            after: $plan->toArray(),
        );

        Toastr::success('Plano atualizado com sucesso.');

        return redirect()->route('admin.finance.plans.index');
    }

    public function toggle(string $id): RedirectResponse
    {
        $before = $this->driverPlanService->find($id)->toArray();
        $plan = $this->driverPlanService->toggleActive($id);

        $this->financeAuditService->log(
            action: 'driver_plan_toggled',
            entityType: get_class($plan),
            entityId: $plan->id,
            before: $before,
            after: $plan->toArray(),
        );

        Toastr::success($plan->is_active ? 'Plano ativado.' : 'Plano desativado.');

        return redirect()->back();
    }
}
