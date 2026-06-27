<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('withdraw_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('withdraw_requests', 'pix_end_to_end_id')) {
                $table->string('pix_end_to_end_id')->nullable()->after('wallet_transaction_id');
            }
            if (!Schema::hasColumn('withdraw_requests', 'pix_payout_gateway')) {
                $table->string('pix_payout_gateway')->nullable()->after('pix_end_to_end_id');
            }
            if (!Schema::hasColumn('withdraw_requests', 'pix_payout_status')) {
                $table->string('pix_payout_status')->nullable()->after('pix_payout_gateway');
            }
            if (!Schema::hasColumn('withdraw_requests', 'pix_payout_reference')) {
                $table->string('pix_payout_reference')->nullable()->after('pix_payout_status');
            }
        });

        if (!Schema::hasTable('finance_pix_payout_logs')) {
            Schema::create('finance_pix_payout_logs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->unsignedBigInteger('withdraw_request_id');
                $table->string('gateway');
                $table->string('event');
                $table->json('payload')->nullable();
                $table->timestamps();

                $table->index('withdraw_request_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_pix_payout_logs');

        Schema::table('withdraw_requests', function (Blueprint $table) {
            foreach (['pix_end_to_end_id', 'pix_payout_gateway', 'pix_payout_status', 'pix_payout_reference'] as $column) {
                if (Schema::hasColumn('withdraw_requests', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
