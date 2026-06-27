<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('withdraw_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('withdraw_requests', 'source')) {
                $table->string('source')->default('legacy')->after('status');
            }
            if (!Schema::hasColumn('withdraw_requests', 'wallet_transaction_id')) {
                $table->uuid('wallet_transaction_id')->nullable()->after('source');
            }
        });
    }

    public function down(): void
    {
        Schema::table('withdraw_requests', function (Blueprint $table) {
            foreach (['source', 'wallet_transaction_id'] as $column) {
                if (Schema::hasColumn('withdraw_requests', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
