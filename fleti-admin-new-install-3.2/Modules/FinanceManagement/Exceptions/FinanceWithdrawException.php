<?php

namespace Modules\FinanceManagement\Exceptions;

use RuntimeException;

class FinanceWithdrawException extends RuntimeException
{
    public function __construct(
        public readonly string $responseCode,
        string $message,
        public readonly int $httpStatus = 403,
    ) {
        parent::__construct($message);
    }
}
