<?php

namespace Modules\FinanceManagement\Lib;

use Modules\FinanceManagement\Entities\FinanceSetting;
use Modules\FinanceManagement\Service\Interfaces\FinanceSettingServiceInterface;

class PaymentGatewayResolver
{
    public static function resolvePixGatewayKey(?FinanceSetting $settings = null): ?string
    {
        $settings ??= app(FinanceSettingServiceInterface::class)->get();

        if (!$settings->pix_payment_enabled) {
            return null;
        }

        $primary = $settings->primary_gateway === 'efi' ? 'efi_pix' : 'mercadopago_pix';

        return self::isPixGatewayAllowed($primary, $settings) ? $primary : self::fallbackPixGateway($settings);
    }

    public static function resolveCardGatewayKey(?FinanceSetting $settings = null): ?string
    {
        $settings ??= app(FinanceSettingServiceInterface::class)->get();

        if (!$settings->card_payment_enabled) {
            return null;
        }

        if ($settings->primary_gateway === 'efi') {
            return null;
        }

        return 'mercadopago';
    }

    public static function isPixGatewayAllowed(string $gatewayKey, ?FinanceSetting $settings = null): bool
    {
        $settings ??= app(FinanceSettingServiceInterface::class)->get();

        if (!$settings->pix_payment_enabled) {
            return false;
        }

        return match ($gatewayKey) {
            'mercadopago_pix' => $settings->primary_gateway !== 'efi' || $settings->hybrid_mode_enabled,
            'efi_pix' => $settings->primary_gateway === 'efi' || $settings->hybrid_mode_enabled,
            default => false,
        };
    }

    public static function availableDigitalMethods(?FinanceSetting $settings = null): array
    {
        $methods = [];
        $pix = self::resolvePixGatewayKey($settings);
        $card = self::resolveCardGatewayKey($settings);

        if ($pix) {
            $methods[] = $pix;
        }
        if ($card) {
            $methods[] = $card;
        }

        return $methods;
    }

    private static function fallbackPixGateway(FinanceSetting $settings): ?string
    {
        if ($settings->hybrid_mode_enabled) {
            $alternate = $settings->primary_gateway === 'efi' ? 'mercadopago_pix' : 'efi_pix';

            return self::isPixGatewayAllowed($alternate, $settings) ? $alternate : null;
        }

        return null;
    }
}
