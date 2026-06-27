<?php

namespace Modules\FinanceManagement\Http\Controllers\Web\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Modules\FinanceManagement\Http\Requests\FinanceSettingUpdateRequest;
use Modules\FinanceManagement\Service\FinanceAuditService;
use Modules\FinanceManagement\Service\Interfaces\FinanceSettingServiceInterface;

class FinanceSettingsController extends Controller
{
    public function __construct(
        private readonly FinanceSettingServiceInterface $financeSettingService,
        private readonly FinanceAuditService $financeAuditService,
    ) {
    }

    public function index(): View
    {
        $settings = $this->financeSettingService->get();

        return view('financemanagement::admin.settings.index', compact('settings'));
    }

    public function update(FinanceSettingUpdateRequest $request): RedirectResponse
    {
        $before = $this->financeSettingService->get()->toArray();
        $settings = $this->financeSettingService->update($request->validated());

        $this->financeAuditService->log(
            action: 'finance_settings_updated',
            entityType: get_class($settings),
            entityId: $settings->id,
            before: $before,
            after: $settings->toArray(),
        );

        return redirect()
            ->route('admin.finance.settings.index')
            ->with('success', translate('settings_updated_successfully'));
    }
}
