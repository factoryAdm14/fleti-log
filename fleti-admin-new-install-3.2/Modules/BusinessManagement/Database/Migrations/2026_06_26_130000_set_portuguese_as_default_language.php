<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $languages = [
            [
                'id' => 1,
                'code' => 'en',
                'direction' => 'ltr',
                'status' => 0,
                'default' => false,
            ],
            [
                'id' => 2,
                'code' => 'pt',
                'direction' => 'ltr',
                'status' => 1,
                'default' => true,
            ],
        ];

        DB::table('business_settings')
            ->where('key_name', 'system_language')
            ->where('settings_type', 'language_settings')
            ->update([
                'value' => json_encode($languages),
                'updated_at' => now(),
            ]);

        DB::table('users')
            ->whereIn('current_language_key', ['en', 'ar', 'bn', 'hi'])
            ->update(['current_language_key' => 'pt']);
    }

    public function down(): void
    {
        $languages = [
            [
                'id' => 1,
                'code' => 'en',
                'direction' => 'ltr',
                'status' => 1,
                'default' => true,
            ],
            [
                'id' => 2,
                'code' => 'pt',
                'direction' => 'ltr',
                'status' => 1,
                'default' => false,
            ],
        ];

        DB::table('business_settings')
            ->where('key_name', 'system_language')
            ->where('settings_type', 'language_settings')
            ->update([
                'value' => json_encode($languages),
                'updated_at' => now(),
            ]);
    }
};
