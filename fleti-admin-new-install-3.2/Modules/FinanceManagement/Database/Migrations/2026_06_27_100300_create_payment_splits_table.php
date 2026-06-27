<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_splits', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('payment_id')->nullable();
            $table->foreignUuid('ride_id')->nullable();
            $table->uuid('order_id')->nullable();
            $table->foreignUuid('driver_id')->nullable();
            $table->decimal('gross_amount', 24, 2);
            $table->decimal('admin_amount', 24, 2)->default(0);
            $table->decimal('driver_amount', 24, 2)->default(0);
            $table->decimal('gateway_fee', 24, 2)->default(0);
            $table->decimal('net_amount', 24, 2)->default(0);
            $table->decimal('commission_percent', 8, 2)->default(0);
            $table->string('status')->default('pending'); // pending|confirmed|failed|refunded
            $table->timestamps();

            $table->index(['ride_id', 'status']);
            $table->index(['driver_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_splits');
    }
};
