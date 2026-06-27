<?php

namespace Tests\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\FinanceManagement\Entities\FinanceSetting;

trait CreatesFinanceSchema
{
    protected function setUpFinanceSchema(): void
    {
        Schema::dropAllTables();

        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('role_id')->nullable();
            $table->string('user_type')->default('driver');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('fcm_token')->nullable();
            $table->string('current_language_key')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('modules')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('module_accesses', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id');
            $table->uuid('role_id');
            $table->string('module_name');
            $table->boolean('view')->default(false);
            $table->boolean('add')->default(false);
            $table->boolean('update')->default(false);
            $table->boolean('delete')->default(false);
            $table->boolean('log')->default(false);
            $table->boolean('export')->default(false);
            $table->timestamps();
        });

        Schema::create('trip_request_fees', function (Blueprint $table) {
            $table->id();
            $table->uuid('trip_request_id');
            $table->decimal('admin_commission', 24, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('finance_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->boolean('commission_mode_enabled')->default(true);
            $table->boolean('subscription_mode_enabled')->default(true);
            $table->boolean('hybrid_mode_enabled')->default(true);
            $table->string('active_mode')->default('hybrid');
            $table->decimal('default_commission_percent', 8, 2)->default(15);
            $table->decimal('min_withdraw_amount', 24, 2)->default(10);
            $table->unsignedInteger('balance_release_days')->default(0);
            $table->boolean('manual_withdraw_approval')->default(true);
            $table->boolean('pix_payment_enabled')->default(true);
            $table->boolean('card_payment_enabled')->default(true);
            $table->string('primary_gateway')->default('mercadopago');
            $table->string('plan_expiry_rule')->default('revert_commission');
            $table->unsignedInteger('plan_grace_period_days')->default(0);
            $table->boolean('auto_pix_payout_enabled')->default(false);
            $table->boolean('withdraw_security_enabled')->default(true);
            $table->decimal('max_withdraw_amount', 24, 2)->default(0);
            $table->unsignedInteger('max_withdraw_requests_per_day')->default(10);
            $table->decimal('max_withdraw_amount_per_day', 24, 2)->default(0);
            $table->boolean('webhook_signature_required')->default(true);
            $table->decimal('payment_amount_tolerance_percent', 5, 2)->default(1);
            $table->timestamps();
        });

        Schema::create('driver_wallets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('driver_id')->unique();
            $table->decimal('available_balance', 24, 2)->default(0);
            $table->decimal('pending_balance', 24, 2)->default(0);
            $table->decimal('blocked_balance', 24, 2)->default(0);
            $table->decimal('total_received', 24, 2)->default(0);
            $table->decimal('total_withdrawn', 24, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('driver_id');
            $table->uuid('wallet_id');
            $table->uuid('ride_id')->nullable();
            $table->uuid('order_id')->nullable();
            $table->string('type');
            $table->decimal('amount', 24, 2);
            $table->string('description')->nullable();
            $table->string('status')->default('completed');
            $table->string('reference')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_splits', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('payment_id')->nullable();
            $table->uuid('ride_id')->nullable();
            $table->uuid('order_id')->nullable();
            $table->uuid('driver_id')->nullable();
            $table->decimal('gross_amount', 24, 2);
            $table->decimal('admin_amount', 24, 2)->default(0);
            $table->decimal('driver_amount', 24, 2)->default(0);
            $table->decimal('gateway_fee', 24, 2)->default(0);
            $table->decimal('net_amount', 24, 2)->default(0);
            $table->decimal('commission_percent', 8, 2)->default(0);
            $table->string('status')->default('confirmed');
            $table->timestamps();
        });

        Schema::create('driver_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 24, 2);
            $table->unsignedInteger('duration_days');
            $table->decimal('commission_percentage', 8, 2)->default(0);
            $table->json('benefits')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('driver_subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('driver_id');
            $table->uuid('plan_id');
            $table->uuid('payment_id')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('renewal_type')->default('manual');
            $table->timestamps();
        });

        Schema::create('finance_audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('action');
            $table->string('entity_type')->nullable();
            $table->uuid('entity_id')->nullable();
            $table->uuid('user_id')->nullable();
            $table->string('user_type')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('withdraw_methods', function (Blueprint $table) {
            $table->id();
            $table->string('method_name');
            $table->text('method_fields');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('withdraw_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id');
            $table->double('amount')->default(0);
            $table->unsignedBigInteger('method_id');
            $table->json('method_fields');
            $table->text('note')->nullable();
            $table->text('driver_note')->nullable();
            $table->text('approval_note')->nullable();
            $table->text('denied_note')->nullable();
            $table->text('rejection_cause')->nullable();
            $table->boolean('is_approved')->nullable();
            $table->string('status')->nullable();
            $table->string('source')->default('legacy');
            $table->uuid('wallet_transaction_id')->nullable();
            $table->string('pix_end_to_end_id')->nullable();
            $table->string('pix_payout_gateway')->nullable();
            $table->string('pix_payout_status')->nullable();
            $table->string('pix_payout_reference')->nullable();
            $table->string('receipt_url')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->uuid('admin_id')->nullable();
            $table->timestamp('under_review_at')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('payer_id', 64)->nullable();
            $table->string('receiver_id', 64)->nullable();
            $table->decimal('payment_amount', 24, 2)->default(0);
            $table->string('gateway_callback_url', 191)->nullable();
            $table->string('hook', 100)->nullable();
            $table->string('transaction_id', 100)->nullable();
            $table->string('currency_code', 20)->default('BRL');
            $table->string('payment_method', 50)->nullable();
            $table->json('additional_data')->nullable();
            $table->boolean('is_paid')->default(false);
            $table->string('attribute')->nullable();
            $table->uuid('attribute_id')->nullable();
            $table->string('payment_platform')->nullable();
            $table->json('payer_information')->nullable();
            $table->json('receiver_information')->nullable();
            $table->string('external_redirect_link')->nullable();
            $table->timestamps();
        });

        FinanceSetting::query()->create([
            'id' => (string) Str::uuid(),
            'commission_mode_enabled' => true,
            'subscription_mode_enabled' => true,
            'hybrid_mode_enabled' => true,
            'active_mode' => 'hybrid',
            'default_commission_percent' => 15,
            'min_withdraw_amount' => 10,
            'balance_release_days' => 0,
            'manual_withdraw_approval' => true,
            'pix_payment_enabled' => true,
            'card_payment_enabled' => true,
            'primary_gateway' => 'mercadopago',
            'plan_expiry_rule' => 'revert_commission',
            'plan_grace_period_days' => 0,
            'auto_pix_payout_enabled' => false,
            'withdraw_security_enabled' => true,
            'max_withdraw_amount' => 0,
            'max_withdraw_requests_per_day' => 10,
            'max_withdraw_amount_per_day' => 0,
            'webhook_signature_required' => true,
            'payment_amount_tolerance_percent' => 1,
        ]);
    }

    protected function seedDriverUser(string $driverId, array $overrides = []): void
    {
        $data = array_merge([
            'id' => $driverId,
            'user_type' => 'driver',
            'first_name' => 'Motorista',
            'last_name' => 'Teste',
            'email' => 'driver@test.com',
            'phone' => '11999999999',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides);

        \Illuminate\Support\Facades\DB::table('users')->insert($data);
    }

    protected function seedWithdrawMethod(): int
    {
        return (int) \Modules\UserManagement\Entities\WithdrawMethod::query()->create([
            'method_name' => 'PIX',
            'method_fields' => [
                ['input_name' => 'pix_key', 'placeholder' => 'Chave PIX'],
            ],
            'is_default' => true,
            'is_active' => true,
        ])->id;
    }
}
