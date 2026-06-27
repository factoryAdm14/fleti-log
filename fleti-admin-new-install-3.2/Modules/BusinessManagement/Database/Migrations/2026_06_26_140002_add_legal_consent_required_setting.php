<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('business_settings')
            ->where('key_name', 'legal_consent_required')
            ->where('settings_type', 'business_information')
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('business_settings')->insert([
            'id' => (string) Str::uuid(),
            'key_name' => 'legal_consent_required',
            'value' => json_encode(1),
            'settings_type' => 'business_information',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('business_settings')
            ->where('key_name', 'legal_consent_required')
            ->where('settings_type', 'business_information')
            ->delete();
    }
};
