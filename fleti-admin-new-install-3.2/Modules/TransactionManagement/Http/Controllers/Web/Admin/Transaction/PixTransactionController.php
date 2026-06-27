<?php

namespace Modules\TransactionManagement\Http\Controllers\Web\Admin\Transaction;

use App\Http\Controllers\Controller;
use App\Exports\StyledReport\ColumnFormat;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\TransactionManagement\Service\PixTransactionService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PixTransactionController extends Controller
{
    use AuthorizesRequests;

    public function __construct(protected PixTransactionService $pixTransactionService)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('transaction_view');

        return $this->render($request, 'transactionmanagement::admin.transaction.pix', false);
    }

    public function report(Request $request): View
    {
        $this->authorize('transaction_view');

        return $this->render($request, 'transactionmanagement::admin.reports.pix', true);
    }

    public function export(Request $request): StreamedResponse|string
    {
        $this->authorize('transaction_export');

        return $this->exportData($request);
    }

    public function reportExport(Request $request): StreamedResponse|string
    {
        $this->authorize('transaction_export');

        return $this->exportData($request);
    }

    protected function render(Request $request, string $view, bool $showReportTabs): View
    {
        $criteria = $request->all();
        $payments = $this->pixTransactionService->index(
            criteria: $criteria,
            limit: paginationLimit(),
            offset: $request->integer('page', 1)
        );
        $summary = $this->pixTransactionService->summary(criteria: $criteria);

        return view($view, [
            'payments' => $payments,
            'summary' => $summary,
            'showReportTabs' => $showReportTabs,
            'pixService' => $this->pixTransactionService,
        ]);
    }

    protected function exportData(Request $request): StreamedResponse|string
    {
        $criteria = $request->all();
        $exportData = $this->pixTransactionService->export(criteria: $criteria);
        $summary = $this->pixTransactionService->summary(criteria: $criteria);
        $config = styledExportConfig(
            $exportData,
            title: translate('pix_transactions_report'),
            summary: [
                translate('total_records') => $exportData->count(),
                translate('paid_amount') => getCurrencyFormat($summary['paid_amount']),
            ],
            filters: [
                translate('search') => $request->search ?? translate('N/A'),
                translate('gateway') => $request->gateway ?? translate('all'),
                translate('status') => $request->is_paid ?? translate('all'),
            ],
            columnFormats: [
                'Amount' => ColumnFormat::CURRENCY,
                'Date' => ColumnFormat::DATETIME,
                'Status' => ColumnFormat::STATUS,
            ],
            fileName: 'pix-transactions-' . time() . '.xlsx',
            headings: ['Payment ID', 'Date', 'Gateway', 'External TX ID', 'Customer', 'Context', 'Reference', 'Amount', 'Status'],
        );

        return exportData($exportData, $request->get('file', 'excel'), '', $config);
    }
}
