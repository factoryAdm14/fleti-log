<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_stops', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('trip_request_id')->index();
            $table->unsignedTinyInteger('stop_order');
            $table->enum('type', ['pickup', 'dropoff']);
            $table->string('address');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->enum('status', ['pending', 'arrived', 'completed', 'failed', 'expired'])->default('pending');
            $table->timestamp('arrived_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('proof_photo')->nullable();
            $table->text('signature')->nullable();
            $table->string('qr_code')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['trip_request_id', 'stop_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_stops');
    }
};
