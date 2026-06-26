<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $settings = [
            ['enable_multi_stop_delivery', '0'],
            ['multi_stop_max_stops', '20'],
        ];

        foreach ($settings as [$key, $value]) {
            $exists = DB::table('business_settings')
                ->where('key_name', $key)
                ->where('settings_type', 'parcel_settings')
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('business_settings')->insert([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'key_name' => $key,
                'value' => json_encode($value),
                'settings_type' => 'parcel_settings',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('business_settings')
            ->whereIn('key_name', ['enable_multi_stop_delivery', 'multi_stop_max_stops'])
            ->where('settings_type', 'parcel_settings')
            ->delete();
    }
};
