<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->boolean('commission_mode_enabled')->default(true);
            $table->boolean('subscription_mode_enabled')->default(false);
            $table->boolean('hybrid_mode_enabled')->default(false);
            $table->string('active_mode')->default('commission'); // commission|subscription|hybrid
            $table->decimal('default_commission_percent', 8, 2)->default(15);
            $table->decimal('min_withdraw_amount', 24, 2)->default(50);
            $table->unsignedInteger('balance_release_days')->default(0);
            $table->boolean('manual_withdraw_approval')->default(true);
            $table->boolean('pix_payment_enabled')->default(true);
            $table->boolean('card_payment_enabled')->default(true);
            $table->string('primary_gateway')->default('mercadopago'); // mercadopago|efi
            $table->string('plan_expiry_rule')->default('revert_commission');
            $table->unsignedInteger('plan_grace_period_days')->default(0);
            $table->boolean('auto_pix_payout_enabled')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_settings');
    }
};
