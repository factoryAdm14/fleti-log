<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('settings')
            ->where('key_name', 'mercadopago_pix')
            ->where('settings_type', 'payment_config')
            ->exists();

        if ($exists) {
            return;
        }

        $values = json_encode([
            'gateway' => 'mercadopago_pix',
            'mode' => 'test',
            'status' => '0',
            'access_token' => null,
            'public_key' => null,
            'webhook_secret' => null,
        ]);

        DB::table('settings')->insert([
            'id' => (string) Str::uuid(),
            'key_name' => 'mercadopago_pix',
            'live_values' => $values,
            'test_values' => $values,
            'settings_type' => 'payment_config',
            'mode' => 'test',
            'is_active' => 0,
            'additional_data' => json_encode([
                'gateway_title' => 'PIX Mercado Pago',
                'gateway_image' => null,
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('settings')
            ->where('key_name', 'mercadopago_pix')
            ->where('settings_type', 'payment_config')
            ->delete();
    }
};
