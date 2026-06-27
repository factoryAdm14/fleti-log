<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $settings = [
            'customer_chatting_setup_status',
            'customer_question_answer_status',
        ];

        foreach ($settings as $key) {
            $exists = DB::table('business_settings')
                ->where('key_name', $key)
                ->where('settings_type', 'chatting_settings')
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('business_settings')->insert([
                'id' => (string) Str::uuid(),
                'key_name' => $key,
                'value' => json_encode('0'),
                'settings_type' => 'chatting_settings',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('business_settings')
            ->whereIn('key_name', ['customer_chatting_setup_status', 'customer_question_answer_status'])
            ->where('settings_type', 'chatting_settings')
            ->delete();
    }
};
