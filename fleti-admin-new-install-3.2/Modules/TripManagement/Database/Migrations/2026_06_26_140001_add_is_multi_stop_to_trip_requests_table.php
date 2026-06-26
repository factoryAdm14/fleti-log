<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('trip_requests', 'is_multi_stop')) {
                $table->boolean('is_multi_stop')->default(false)->after('is_parcel_delivery_proof_enabled');
            }
        });
    }

    public function down(): void
    {
        Schema::table('trip_requests', function (Blueprint $table) {
            if (Schema::hasColumn('trip_requests', 'is_multi_stop')) {
                $table->dropColumn('is_multi_stop');
            }
        });
    }
};
