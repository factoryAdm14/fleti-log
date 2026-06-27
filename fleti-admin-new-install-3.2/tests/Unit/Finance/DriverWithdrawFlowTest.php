<?php

namespace Tests\Unit\Finance;

use Illuminate\Support\Str;
use Modules\FinanceManagement\Entities\DriverWallet;
use Modules\FinanceManagement\Exceptions\FinanceWithdrawException;
use Modules\FinanceManagement\Service\DriverWithdrawService;
use Modules\FinanceManagement\Service\FinanceWithdrawSecurityService;
use Modules\FinanceManagement\Service\FinanceWithdrawAdminService;
use Modules\UserManagement\Entities\WithdrawRequest;
use Tests\Support\FinanceTestCase;

class DriverWithdrawFlowTest extends FinanceTestCase
{
    private string $driverId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->driverId = (string) Str::uuid();
        $this->seedDriverUser($this->driverId);
        DriverWallet::query()->create([
            'driver_id' => $this->driverId,
            'available_balance' => 200,
            'pending_balance' => 0,
            'blocked_balance' => 0,
            'total_received' => 200,
            'total_withdrawn' => 0,
        ]);
    }

    public function test_driver_can_request_withdraw_and_blocks_balance(): void
    {
        $methodId = $this->seedWithdrawMethod();

        $withdraw = app(DriverWithdrawService::class)->requestWithdraw(
            driverId: $this->driverId,
            amount: 50,
            withdrawMethodId: $methodId,
            methodFields: ['pix_key' => 'test@email.com'],
        );

        $this->assertSame(PENDING, $withdraw->status);
        $this->assertSame(DriverWithdrawService::SOURCE_FINANCE, $withdraw->source);

        $wallet = DriverWallet::query()->where('driver_id', $this->driverId)->first();
        $this->assertSame(150.0, (float) $wallet->available_balance);
        $this->assertSame(50.0, (float) $wallet->blocked_balance);
    }

    public function test_duplicate_open_withdraw_is_blocked(): void
    {
        $methodId = $this->seedWithdrawMethod();
        $service = app(DriverWithdrawService::class);

        $service->requestWithdraw($this->driverId, 30, $methodId, ['pix_key' => 'a@b.com']);

        $this->expectException(FinanceWithdrawException::class);
        $this->expectExceptionMessage('Já existe uma solicitação de saque em aberto.');

        $service->requestWithdraw($this->driverId, 20, $methodId, ['pix_key' => 'a@b.com']);
    }

    public function test_insufficient_balance_is_rejected(): void
    {
        $methodId = $this->seedWithdrawMethod();

        $this->expectException(FinanceWithdrawException::class);
        $this->expectExceptionMessage('Saldo disponível insuficiente');

        app(DriverWithdrawService::class)->requestWithdraw(
            $this->driverId,
            500,
            $methodId,
            ['pix_key' => 'a@b.com'],
        );
    }

    public function test_admin_can_approve_deny_and_settle_withdraw(): void
    {
        $methodId = $this->seedWithdrawMethod();
        $adminId = (string) Str::uuid();

        $withdraw = app(DriverWithdrawService::class)->requestWithdraw(
            $this->driverId,
            60,
            $methodId,
            ['pix_key' => 'pix@test.com'],
        );

        $approved = app(FinanceWithdrawAdminService::class)->approve($withdraw->id, $adminId, 'ok');
        $this->assertSame(APPROVED, $approved->status);

        $settled = app(FinanceWithdrawAdminService::class)->settle($approved->id, $adminId);
        $this->assertSame(SETTLED, $settled->status);

        $wallet = DriverWallet::query()->where('driver_id', $this->driverId)->first();
        $this->assertSame(140.0, (float) $wallet->available_balance);
        $this->assertSame(0.0, (float) $wallet->blocked_balance);
        $this->assertSame(60.0, (float) $wallet->total_withdrawn);
    }

    public function test_denied_withdraw_restores_available_balance(): void
    {
        $methodId = $this->seedWithdrawMethod();
        $adminId = (string) Str::uuid();

        $withdraw = app(DriverWithdrawService::class)->requestWithdraw(
            $this->driverId,
            40,
            $methodId,
            ['pix_key' => 'pix@test.com'],
        );

        app(FinanceWithdrawAdminService::class)->deny($withdraw->id, $adminId, 'dados inválidos');

        $wallet = DriverWallet::query()->where('driver_id', $this->driverId)->first();
        $this->assertSame(200.0, (float) $wallet->available_balance);
        $this->assertSame(0.0, (float) $wallet->blocked_balance);
        $this->assertSame(DENIED, WithdrawRequest::query()->find($withdraw->id)->status);
    }

    public function test_security_service_blocks_excessive_daily_requests(): void
    {
        \Modules\FinanceManagement\Entities\FinanceSetting::query()->first()->update([
            'max_withdraw_requests_per_day' => 1,
            'max_withdraw_amount' => 0,
            'max_withdraw_amount_per_day' => 0,
        ]);

        $methodId = $this->seedWithdrawMethod();
        $service = app(DriverWithdrawService::class);

        $first = $service->requestWithdraw($this->driverId, 20, $methodId, ['pix_key' => 'a@b.com']);
        app(FinanceWithdrawAdminService::class)->deny($first->id, (string) Str::uuid(), 'test');

        $this->expectException(FinanceWithdrawException::class);
        $service->requestWithdraw($this->driverId, 20, $methodId, ['pix_key' => 'a@b.com']);
    }

    public function test_security_service_blocks_amount_above_limit(): void
    {
        \Modules\FinanceManagement\Entities\FinanceSetting::query()->first()->update([
            'max_withdraw_amount' => 100,
        ]);

        $methodId = $this->seedWithdrawMethod();

        $this->expectException(FinanceWithdrawException::class);

        app(DriverWithdrawService::class)->requestWithdraw(
            $this->driverId,
            150,
            $methodId,
            ['pix_key' => 'a@b.com'],
        );
    }
}
