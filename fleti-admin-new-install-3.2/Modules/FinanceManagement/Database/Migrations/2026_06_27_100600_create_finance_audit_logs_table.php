<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('action');
            $table->string('entity_type')->nullable();
            $table->uuid('entity_id')->nullable();
            $table->foreignUuid('user_id')->nullable();
            $table->string('user_type')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_audit_logs');
    }
};
