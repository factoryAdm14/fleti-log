<?php

namespace Modules\UserManagement\Http\Controllers\Web\Admin\Driver;

use App\Http\Controllers\BaseController;
use App\Exports\StyledReport\ColumnFormat;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\TransactionManagement\Service\Interfaces\TransactionServiceInterface;
use Modules\TransactionManagement\Traits\TransactionTrait;
use Modules\UserManagement\Http\Requests\DriverWalletStoreOrUpdateRequest;
use Modules\UserManagement\Service\Interfaces\DriverAccountServiceInterface;
use Modules\UserManagement\Service\Interfaces\DriverServiceInterface;

class DriverWalletController extends BaseController
{
    use AuthorizesRequests, TransactionTrait;

    public function __construct(
        protected DriverAccountServiceInterface $driverAccountService,
        protected TransactionServiceInterface $transactionService,
        protected DriverServiceInterface $driverService
    ) {
        parent::__construct($driverAccountService);
    }

    public function index(?Request $request, string $type = null): View|Collection|LengthAwarePaginator|null|callable|RedirectResponse
    {
        $this->authorize('user_view');
        $request?->validate([
            'data' => Rule::in([ALL_TIME, THIS_WEEK, LAST_WEEK, THIS_MONTH, LAST_MONTH, THIS_YEAR, CUSTOM_DATE]),
            'start' => 'required_if:data,custom_date',
            'end' => 'required_if:data,custom_date',
        ]);

        $transactions = $this->transactionService->driverWalletTransaction(
            data: $request?->all(),
            orderBy: ['created_at' => 'desc'],
            limit: paginationLimit(),
            offset: $request['page'] ?? 1
        );

        return view('usermanagement::admin.driver.wallet.index', compact('transactions'));
    }

    public function store(DriverWalletStoreOrUpdateRequest $request): RedirectResponse
    {
        $this->authorize('user_add');

        DB::beginTransaction();

        try {
            if ($this->isAllDrivers($request['driver_id'])) {
                $whereHasRelations = [
                    'user' => ['user_type' => DRIVER, 'is_active' => true],
                ];
                $relations = [
                    'user' => [
                        ['user_type', '=', DRIVER],
                        ['is_active', '=', true],
                    ],
                ];
                $driverAccounts = $this->driverAccountService->getBy(
                    whereHasRelations: $whereHasRelations,
                    relations: $relations
                );
                $driverAccountIds = $driverAccounts->pluck('id')->toArray();
                $this->driverAccountService->updateManyWithIncrement(
                    ids: $driverAccountIds,
                    column: 'receivable_balance',
                    amount: $request['amount']
                );
                $driverAccounts = $this->driverAccountService->getBy(
                    whereHasRelations: $whereHasRelations,
                    relations: $relations
                );

                foreach ($driverAccounts as $driverAccount) {
                    $this->creditDriverAndNotify($driverAccount, $request->validated());
                }
            } else {
                $driverAccount = $this->driverAccountService->findOneBy(criteria: ['user_id' => $request['driver_id']]);
                if (! $driverAccount) {
                    throw new \RuntimeException(translate('driver_not_found'));
                }

                $this->driverAccountService->update(id: $driverAccount->id, data: [
                    'receivable_balance' => $driverAccount->receivable_balance + $request['amount'],
                ]);
                $driverAccount = $this->driverAccountService->findOneBy(
                    criteria: ['user_id' => $request['driver_id']],
                    relations: ['user']
                );
                $this->creditDriverAndNotify($driverAccount, $request->validated());
            }

            DB::commit();
            Toastr::success(DRIVER_FUND_STORE_200['message']);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            Toastr::error($e->getMessage() ?: DEFAULT_400['message']);
        }

        return redirect()->back();
    }

    public function export(Request $request)
    {
        $this->authorize('user_export');
        $transactions = $this->transactionService->driverWalletTransaction(
            $request?->all(),
            orderBy: ['created_at' => 'desc']
        );
        $exportData = $this->driverAccountService->export($transactions);

        $driverName = translate('All');
        if ($request->user_id && in_array($request->user_id, ['all', 0, '0'], true)) {
            $driverName = translate('all_driver');
        } elseif ($request->user_id) {
            $user = $this->driverService->findOne(id: $request->user_id);
            if ($user) {
                $driverName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
            }
        }

        $config = styledExportConfig(
            $exportData,
            title: translate('driver_wallet_report'),
            summary: ['Total Records' => $exportData->count()],
            filters: [
                'Driver' => $driverName,
                'Range' => $request->data ?? translate('All Time'),
                'From' => $request->start ?? translate('N/A'),
                'To' => $request->end ?? translate('N/A'),
                'Search' => $request->search ?? translate('N/A'),
            ],
            columnFormats: [
                'Debit' => ColumnFormat::CURRENCY,
                'Credit' => ColumnFormat::CURRENCY,
                'Balance' => ColumnFormat::CURRENCY,
                'Transaction Date' => ColumnFormat::DATETIME,
            ],
            fileName: 'driver-wallet-' . time() . '.xlsx',
            headings: ['Transaction Id', 'Reference', 'Transaction Date', 'Transaction To', 'Debit', 'Credit', 'Balance'],
        );

        return exportData($exportData, $request['file'], '', $config);
    }

    protected function isAllDrivers(mixed $driverId): bool
    {
        return in_array($driverId, ['all', 0, '0'], true);
    }

    protected function creditDriverAndNotify($driverAccount, array $data): void
    {
        $this->addDriverFundByAdmin(driverAccount: $driverAccount, data: $data);

        $push = getNotification('fund_added_by_admin');
        sendDeviceNotification(
            fcm_token: $driverAccount?->user?->fcm_token,
            title: translate(key: $push['title'], locale: $driverAccount?->user?->current_language_key),
            description: textVariableDataFormat(
                value: $push['description'],
                walletAmount: set_currency_symbol($data['amount']),
                locale: $driverAccount?->user?->current_language_key
            ),
            status: $push['status'],
            ride_request_id: $driverAccount?->user?->id,
            notification_type: 'fund',
            action: $push['action'],
            user_id: $driverAccount?->user?->id
        );
    }
}
