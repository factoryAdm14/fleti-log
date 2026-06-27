<?php

namespace App\Lib;

use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Structured operational logging for Fleti Enterprise v4 (FASE 019).
 */
class FletiObservability
{
    public const DOMAIN_LOGIN = 'login';
    public const DOMAIN_RIDE = 'ride';
    public const DOMAIN_DELIVERY = 'delivery';
    public const DOMAIN_WALLET = 'wallet';
    public const DOMAIN_PIX = 'pix';
    public const DOMAIN_ZONE = 'zone';
    public const DOMAIN_WEBHOOK = 'webhook';
    public const DOMAIN_API = 'api';
    public const DOMAIN_MAP = 'map';
    public const DOMAIN_BUILD = 'build';
    public const DOMAIN_PAYMENT = 'payment';

    private const SENSITIVE_KEYS = [
        'password',
        'token',
        'access_token',
        'secret',
        'authorization',
        'certificate_password',
        'qr_code',
        'qr_code_base64',
    ];

    public static function login(string $event, array $context = [], string $level = 'info'): void
    {
        self::log(self::DOMAIN_LOGIN, $event, $context, $level);
    }

    public static function ride(string $event, array $context = [], string $level = 'info'): void
    {
        self::log(self::DOMAIN_RIDE, $event, $context, $level);
    }

    public static function delivery(string $event, array $context = [], string $level = 'info'): void
    {
        self::log(self::DOMAIN_DELIVERY, $event, $context, $level);
    }

    public static function wallet(string $event, array $context = [], string $level = 'info'): void
    {
        self::log(self::DOMAIN_WALLET, $event, $context, $level);
    }

    public static function pix(string $event, array $context = [], string $level = 'info'): void
    {
        self::log(self::DOMAIN_PIX, $event, $context, $level);
    }

    public static function zone(string $event, array $context = [], string $level = 'info'): void
    {
        self::log(self::DOMAIN_ZONE, $event, $context, $level);
    }

    public static function webhook(string $event, array $context = [], string $level = 'info'): void
    {
        self::log(self::DOMAIN_WEBHOOK, $event, $context, $level);
    }

    public static function apiError(string $event, array $context = [], string $level = 'warning'): void
    {
        self::log(self::DOMAIN_API, $event, $context, $level);
    }

    public static function mapError(string $event, array $context = [], string $level = 'warning'): void
    {
        self::log(self::DOMAIN_MAP, $event, $context, $level);
    }

    public static function buildError(string $event, array $context = [], string $level = 'error'): void
    {
        self::log(self::DOMAIN_BUILD, $event, $context, $level);
    }

    public static function paymentFailure(string $event, array $context = [], string $level = 'warning'): void
    {
        self::log(self::DOMAIN_PAYMENT, $event, $context, $level);
    }

    public static function exception(string $domain, string $event, Throwable $throwable, array $context = []): void
    {
        self::log($domain, $event, array_merge($context, [
            'exception' => $throwable::class,
            'message' => $throwable->getMessage(),
        ]), 'error');
    }

    public static function log(string $domain, string $event, array $context = [], string $level = 'info'): void
    {
        $payload = array_merge([
            'domain' => $domain,
            'event' => $event,
            'timestamp' => now()->toIso8601String(),
            'request_id' => request()?->header('X-Request-ID') ?? request()?->header('X-Correlation-ID'),
            'ip' => request()?->ip(),
            'path' => request()?->path(),
            'method' => request()?->method(),
        ], self::sanitize($context));

        Log::channel('fleti')->log($level, "[fleti:{$domain}] {$event}", $payload);
    }

    private static function sanitize(array $context): array
    {
        $sanitized = [];

        foreach ($context as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = self::sanitize($value);
                continue;
            }

            if (self::isSensitiveKey((string) $key)) {
                $sanitized[$key] = '[redacted]';
                continue;
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }

    private static function isSensitiveKey(string $key): bool
    {
        $normalized = strtolower($key);

        foreach (self::SENSITIVE_KEYS as $sensitive) {
            if (str_contains($normalized, $sensitive)) {
                return true;
            }
        }

        return false;
    }
}
