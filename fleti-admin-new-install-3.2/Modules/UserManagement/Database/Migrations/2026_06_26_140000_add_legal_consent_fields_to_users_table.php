<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'terms_accepted_at')) {
                $table->timestamp('terms_accepted_at')->nullable()->after('logged_in_via');
            }
            if (!Schema::hasColumn('users', 'privacy_accepted_at')) {
                $table->timestamp('privacy_accepted_at')->nullable()->after('terms_accepted_at');
            }
            if (!Schema::hasColumn('users', 'location_consent_at')) {
                $table->timestamp('location_consent_at')->nullable()->after('privacy_accepted_at');
            }
            if (!Schema::hasColumn('users', 'marketing_consent_at')) {
                $table->timestamp('marketing_consent_at')->nullable()->after('location_consent_at');
            }
            if (!Schema::hasColumn('users', 'terms_version')) {
                $table->string('terms_version', 50)->nullable()->after('marketing_consent_at');
            }
            if (!Schema::hasColumn('users', 'privacy_version')) {
                $table->string('privacy_version', 50)->nullable()->after('terms_version');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = [
                'terms_accepted_at',
                'privacy_accepted_at',
                'location_consent_at',
                'marketing_consent_at',
                'terms_version',
                'privacy_version',
            ];
            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
