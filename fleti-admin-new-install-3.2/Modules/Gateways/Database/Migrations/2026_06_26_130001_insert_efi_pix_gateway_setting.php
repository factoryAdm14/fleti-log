<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('settings')
            ->where('key_name', 'efi_pix')
            ->where('settings_type', 'payment_config')
            ->exists();

        if ($exists) {
            return;
        }

        $values = json_encode([
            'gateway' => 'efi_pix',
            'mode' => 'test',
            'status' => '0',
            'client_id' => null,
            'client_secret' => null,
            'certificate_file' => null,
            'certificate_password' => null,
            'pix_key' => null,
        ]);

        DB::table('settings')->insert([
            'id' => (string) Str::uuid(),
            'key_name' => 'efi_pix',
            'live_values' => $values,
            'test_values' => $values,
            'settings_type' => 'payment_config',
            'mode' => 'test',
            'is_active' => 0,
            'additional_data' => json_encode([
                'gateway_title' => 'PIX EFI',
                'gateway_image' => null,
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('settings')
            ->where('key_name', 'efi_pix')
            ->where('settings_type', 'payment_config')
            ->delete();
    }
};
