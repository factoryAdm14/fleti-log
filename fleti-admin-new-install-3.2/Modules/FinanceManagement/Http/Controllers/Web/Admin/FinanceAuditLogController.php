<?php

namespace Modules\FinanceManagement\Http\Controllers\Web\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Modules\FinanceManagement\Service\FinanceAuditLogService;

class FinanceAuditLogController extends Controller
{
    public function __construct(
        private readonly FinanceAuditLogService $auditLogService,
    ) {
    }

    public function index(Request $request): View
    {
        abort_unless(Gate::allows('finance_log'), 403);

        $logs = $this->auditLogService->paginate([
            'action' => $request->get('action'),
            'entity_type' => $request->get('entity_type'),
            'user_id' => $request->get('user_id'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
        ], paginationLimit());

        return view('financemanagement::admin.audit.index', compact('logs'));
    }
}
