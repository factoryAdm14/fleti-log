<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('driver_id');
            $table->foreignUuid('plan_id');
            $table->uuid('payment_id')->nullable();
            $table->string('status')->default('pending'); // pending|active|expired|cancelled|failed
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('renewal_type')->default('manual'); // manual|auto
            $table->timestamps();

            $table->index(['driver_id', 'status']);
            $table->index(['expires_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_subscriptions');
    }
};
