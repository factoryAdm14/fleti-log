<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_wallets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('driver_id')->unique();
            $table->decimal('available_balance', 24, 2)->default(0);
            $table->decimal('pending_balance', 24, 2)->default(0);
            $table->decimal('blocked_balance', 24, 2)->default(0);
            $table->decimal('total_received', 24, 2)->default(0);
            $table->decimal('total_withdrawn', 24, 2)->default(0);
            $table->timestamps();

            $table->index('driver_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_wallets');
    }
};
