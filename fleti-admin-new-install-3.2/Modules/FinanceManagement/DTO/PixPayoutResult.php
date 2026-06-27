<?php

namespace Modules\FinanceManagement\DTO;

class PixPayoutResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $gateway,
        public readonly ?string $endToEndId = null,
        public readonly ?string $reference = null,
        public readonly ?string $status = null,
        public readonly ?string $message = null,
    ) {
    }

    public static function success(
        string $gateway,
        ?string $endToEndId = null,
        ?string $reference = null,
        ?string $status = 'sent',
    ): self {
        return new self(true, $gateway, $endToEndId, $reference, $status);
    }

    public static function failed(string $gateway, string $message, ?string $status = 'failed'): self
    {
        return new self(false, $gateway, null, null, $status, $message);
    }
}
