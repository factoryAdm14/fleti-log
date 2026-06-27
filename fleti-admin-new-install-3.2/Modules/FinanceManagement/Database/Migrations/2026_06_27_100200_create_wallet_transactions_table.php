<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('driver_id');
            $table->foreignUuid('wallet_id');
            $table->foreignUuid('ride_id')->nullable();
            $table->uuid('order_id')->nullable();
            $table->string('type'); // credit|debit|commission|withdraw|refund|bonus|adjustment
            $table->decimal('amount', 24, 2);
            $table->string('description')->nullable();
            $table->string('status')->default('completed'); // pending|completed|failed|cancelled
            $table->string('reference')->nullable();
            $table->timestamps();

            $table->index(['driver_id', 'created_at']);
            $table->index(['wallet_id', 'type']);
            $table->index('ride_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
