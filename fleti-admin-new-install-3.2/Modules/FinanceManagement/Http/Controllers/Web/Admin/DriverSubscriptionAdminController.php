<?php

namespace Modules\FinanceManagement\Http\Controllers\Web\Admin;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\FinanceManagement\Exceptions\FinanceWithdrawException;
use Modules\UserManagement\Entities\User;
use Modules\FinanceManagement\Http\Requests\ManualSubscriptionRequest;
use Modules\FinanceManagement\Service\Interfaces\DriverPlanServiceInterface;
use Modules\FinanceManagement\Service\Interfaces\DriverSubscriptionServiceInterface;

class DriverSubscriptionAdminController extends Controller
{
    public function __construct(
        private readonly DriverSubscriptionServiceInterface $subscriptionService,
        private readonly DriverPlanServiceInterface $driverPlanService,
    ) {
    }

    public function index(): View
    {
        $status = request('status', 'all');
        $subscriptions = $this->subscriptionService->paginateForAdmin([
            'status' => $status,
            'search' => request('search'),
        ], paginationLimit());

        $plans = $this->driverPlanService->listActive();

        return view('financemanagement::admin.subscriptions.index', compact('subscriptions', 'status', 'plans'));
    }

    public function activate(ManualSubscriptionRequest $request): RedirectResponse
    {
        try {
            $this->subscriptionService->activateManually(
                driverId: $request->driver_id,
                planId: $request->plan_id,
                adminId: (string) auth()->id(),
            );
            Toastr::success('Assinatura ativada manualmente.');
        } catch (FinanceWithdrawException $e) {
            Toastr::error($e->getMessage());
        }

        return redirect()->route('admin.finance.subscriptions.index');
    }

    public function cancel(string $id): RedirectResponse
    {
        $this->subscriptionService->cancel($id, (string) auth()->id());
        Toastr::success('Assinatura cancelada.');

        return redirect()->back();
    }

    public function searchDrivers(Request $request): JsonResponse
    {
        $search = trim((string) $request->get('search', ''));

        $query = User::query()
            ->where('user_type', DRIVER)
            ->orderBy('first_name');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%");
            });
        }

        $drivers = $query
            ->limit(25)
            ->get(['id', 'first_name', 'last_name', 'phone']);

        return response()->json(
            $drivers->map(fn (User $driver) => [
                'id' => $driver->id,
                'text' => trim("{$driver->first_name} {$driver->last_name}") . " ({$driver->phone})",
            ])->values()
        );
    }
}
