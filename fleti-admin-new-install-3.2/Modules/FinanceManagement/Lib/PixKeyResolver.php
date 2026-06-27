<?php

namespace Modules\FinanceManagement\Lib;

use Modules\UserManagement\Entities\WithdrawRequest;

class PixKeyResolver
{
    private const KEY_HINTS = [
        'pix_key',
        'chave_pix',
        'chave',
        'pix',
        'key',
        'cpf',
        'cnpj',
        'email',
        'e-mail',
        'phone',
        'telefone',
        'celular',
        'mobile',
    ];

    public static function fromWithdrawRequest(WithdrawRequest $withdraw): ?string
    {
        $fields = $withdraw->method_fields ?? [];
        if (!is_array($fields)) {
            return null;
        }

        foreach (self::KEY_HINTS as $hint) {
            foreach ($fields as $key => $value) {
                if (stripos((string) $key, $hint) !== false && self::isValidPixKey($value)) {
                    return self::normalizePixKey((string) $value);
                }
            }
        }

        foreach ($fields as $value) {
            if (self::isValidPixKey($value)) {
                return self::normalizePixKey((string) $value);
            }
        }

        return null;
    }

    private static function isValidPixKey(mixed $value): bool
    {
        if (!is_string($value) && !is_numeric($value)) {
            return false;
        }

        $value = trim((string) $value);

        return strlen($value) >= 5;
    }

    private static function normalizePixKey(string $value): string
    {
        $value = trim($value);

        if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return strtolower($value);
        }

        $digits = preg_replace('/\D+/', '', $value);
        if (strlen($digits) === 11 || strlen($digits) === 14) {
            return $digits;
        }

        if (str_starts_with($value, '+')) {
            return $value;
        }

        if (strlen($digits) >= 10 && strlen($digits) <= 13) {
            return '+55' . ltrim($digits, '0');
        }

        return $value;
    }
}
