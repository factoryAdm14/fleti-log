<?php

namespace Modules\FinanceManagement\DTO;

class WebhookProcessingResult
{
    public function __construct(
        public readonly bool $accepted,
        public readonly string $gateway,
        public readonly ?string $paymentRequestId = null,
        public readonly ?string $status = null,
        public readonly ?string $message = null,
    ) {
    }

    public static function accepted(string $gateway, ?string $paymentRequestId = null, ?string $status = null): self
    {
        return new self(true, $gateway, $paymentRequestId, $status);
    }

    public static function rejected(string $gateway, string $message): self
    {
        return new self(false, $gateway, null, null, $message);
    }
}
