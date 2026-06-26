<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('efi_pix_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('payment_request_id')->nullable()->index();
            $table->string('event', 50);
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('efi_pix_logs');
    }
};
