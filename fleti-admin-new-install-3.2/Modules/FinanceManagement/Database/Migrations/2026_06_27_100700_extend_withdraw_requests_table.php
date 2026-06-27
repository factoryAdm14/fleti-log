<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('withdraw_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('withdraw_requests', 'receipt_url')) {
                $table->string('receipt_url')->nullable()->after('denied_note');
            }
            if (!Schema::hasColumn('withdraw_requests', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('receipt_url');
            }
            if (!Schema::hasColumn('withdraw_requests', 'admin_id')) {
                $table->foreignUuid('admin_id')->nullable()->after('paid_at');
            }
            if (!Schema::hasColumn('withdraw_requests', 'under_review_at')) {
                $table->timestamp('under_review_at')->nullable()->after('admin_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('withdraw_requests', function (Blueprint $table) {
            $columns = ['receipt_url', 'paid_at', 'admin_id', 'under_review_at'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('withdraw_requests', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
