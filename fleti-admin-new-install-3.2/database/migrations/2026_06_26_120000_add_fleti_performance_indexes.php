<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fleti Enterprise v4 — FASE 011 performance indexes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_requests', function (Blueprint $table) {
            $table->index(['customer_id', 'created_at'], 'trip_requests_customer_created_idx');
            $table->index(['driver_id', 'current_status', 'created_at'], 'trip_requests_driver_status_created_idx');
            $table->index('zone_id', 'trip_requests_zone_id_idx');
            $table->index('current_status', 'trip_requests_current_status_idx');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'transactions_user_created_idx');
            $table->index('account', 'transactions_account_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index(['user_type', 'is_active'], 'users_type_active_idx');
        });
    }

    public function down(): void
    {
        Schema::table('trip_requests', function (Blueprint $table) {
            $table->dropIndex('trip_requests_customer_created_idx');
            $table->dropIndex('trip_requests_driver_status_created_idx');
            $table->dropIndex('trip_requests_zone_id_idx');
            $table->dropIndex('trip_requests_current_status_idx');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('transactions_user_created_idx');
            $table->dropIndex('transactions_account_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_type_active_idx');
        });
    }
};
