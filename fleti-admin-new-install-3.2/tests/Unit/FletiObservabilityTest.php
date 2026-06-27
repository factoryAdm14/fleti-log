<?php

namespace Tests\Unit;

use App\Lib\FletiObservability;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class FletiObservabilityTest extends TestCase
{
    public function test_login_log_uses_fleti_channel(): void
    {
        Log::shouldReceive('channel')
            ->once()
            ->with('fleti')
            ->andReturnSelf();

        Log::shouldReceive('log')
            ->once()
            ->withArgs(function (string $level, string $message, array $context) {
                return $level === 'info'
                    && str_contains($message, '[fleti:login] success')
                    && $context['domain'] === 'login'
                    && $context['event'] === 'success'
                    && $context['user_id'] === 'user-1';
            });

        FletiObservability::login('success', ['user_id' => 'user-1', 'password' => 'secret']);
    }

    public function test_sensitive_fields_are_redacted(): void
    {
        Log::shouldReceive('channel')->with('fleti')->andReturnSelf();
        Log::shouldReceive('log')
            ->once()
            ->withArgs(function (string $level, string $message, array $context) {
                return $context['access_token'] === '[redacted]'
                    && $context['user_id'] === '42';
            });

        FletiObservability::pix('paid', [
            'user_id' => '42',
            'access_token' => 'abc123',
        ]);
    }
}
