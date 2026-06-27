<?php

namespace Tests\Feature\Finance;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Modules\UserManagement\Entities\User;
use Tests\Support\FinanceTestCase;

class FinanceAdminAccessTest extends FinanceTestCase
{
    public function test_super_admin_can_manage_finance_withdraws(): void
    {
        $adminId = (string) Str::uuid();
        DB::table('users')->insert([
            'id' => $adminId,
            'user_type' => 'super-admin',
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $admin = User::query()->find($adminId);
        $this->actingAs($admin);

        $this->assertTrue(Gate::allows('finance_withdraw_manage'));
        $this->assertTrue(Gate::allows('finance_log'));
    }

    public function test_employee_without_permission_cannot_manage_withdraws(): void
    {
        $roleId = (string) Str::uuid();
        DB::table('roles')->insert([
            'id' => $roleId,
            'name' => 'Finance Viewer',
            'modules' => json_encode(['finance_management']),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $employeeId = (string) Str::uuid();
        DB::table('users')->insert([
            'id' => $employeeId,
            'role_id' => $roleId,
            'user_type' => 'admin-employee',
            'first_name' => 'Emp',
            'last_name' => 'Finance',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('module_accesses')->insert([
            'user_id' => $employeeId,
            'role_id' => $roleId,
            'module_name' => 'finance_management',
            'view' => true,
            'update' => false,
            'log' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $employee = User::query()->with('role', 'moduleAccess')->find($employeeId);
        $this->actingAs($employee);

        $this->assertTrue(Gate::allows('finance_view'));
        $this->assertFalse(Gate::allows('finance_withdraw_manage'));
        $this->assertTrue(Gate::allows('finance_log'));
    }

    public function test_finance_dashboard_route_requires_admin_auth(): void
    {
        $response = $this->get('/admin/finance/dashboard');

        $response->assertRedirect();
    }
}
