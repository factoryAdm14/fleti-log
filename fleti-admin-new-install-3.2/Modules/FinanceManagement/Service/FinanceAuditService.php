<?php

namespace Modules\FinanceManagement\Service;

use Illuminate\Support\Facades\Request;
use Modules\FinanceManagement\Entities\FinanceAuditLog;
use Modules\FinanceManagement\Entities\WalletTransaction;

class FinanceAuditService
{
    public function log(
        string $action,
        ?string $entityType = null,
        ?string $entityId = null,
        ?array $before = null,
        ?array $after = null,
        ?string $notes = null,
    ): FinanceAuditLog {
        $user = auth()->user();

        return FinanceAuditLog::query()->create([
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'user_id' => $user?->id,
            'user_type' => $user?->user_type,
            'ip_address' => Request::ip(),
            'before' => $before,
            'after' => $after,
            'notes' => $notes,
        ]);
    }

    public function logSystem(
        string $action,
        ?string $entityType = null,
        ?string $entityId = null,
        ?array $before = null,
        ?array $after = null,
        ?string $notes = null,
    ): FinanceAuditLog {
        return FinanceAuditLog::query()->create([
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'user_id' => null,
            'user_type' => 'system',
            'ip_address' => Request::ip(),
            'before' => $before,
            'after' => $after,
            'notes' => $notes,
        ]);
    }

    public function logWalletTransaction(WalletTransaction $transaction, string $action = 'wallet_transaction_created'): FinanceAuditLog
    {
        return $this->log(
            action: $action,
            entityType: WalletTransaction::class,
            entityId: $transaction->id,
            after: $transaction->toArray(),
        );
    }
}
