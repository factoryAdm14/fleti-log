<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('finance_settings', 'withdraw_security_enabled')) {
                $table->boolean('withdraw_security_enabled')->default(true)->after('auto_pix_payout_enabled');
            }
            if (!Schema::hasColumn('finance_settings', 'max_withdraw_amount')) {
                $table->decimal('max_withdraw_amount', 24, 2)->default(0)->after('withdraw_security_enabled');
            }
            if (!Schema::hasColumn('finance_settings', 'max_withdraw_requests_per_day')) {
                $table->unsignedInteger('max_withdraw_requests_per_day')->default(3)->after('max_withdraw_amount');
            }
            if (!Schema::hasColumn('finance_settings', 'max_withdraw_amount_per_day')) {
                $table->decimal('max_withdraw_amount_per_day', 24, 2)->default(0)->after('max_withdraw_requests_per_day');
            }
            if (!Schema::hasColumn('finance_settings', 'webhook_signature_required')) {
                $table->boolean('webhook_signature_required')->default(true)->after('max_withdraw_amount_per_day');
            }
            if (!Schema::hasColumn('finance_settings', 'payment_amount_tolerance_percent')) {
                $table->decimal('payment_amount_tolerance_percent', 5, 2)->default(1)->after('webhook_signature_required');
            }
        });
    }

    public function down(): void
    {
        Schema::table('finance_settings', function (Blueprint $table) {
            $columns = [
                'withdraw_security_enabled',
                'max_withdraw_amount',
                'max_withdraw_requests_per_day',
                'max_withdraw_amount_per_day',
                'webhook_signature_required',
                'payment_amount_tolerance_percent',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('finance_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
