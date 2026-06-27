<?php

namespace Modules\FinanceManagement\Service;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\FinanceManagement\Entities\FinanceAuditLog;

class FinanceAuditLogService
{
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = FinanceAuditLog::query()->orderByDesc('created_at');

        if (!empty($filters['action'])) {
            $query->where('action', 'like', '%' . $filters['action'] . '%');
        }

        if (!empty($filters['entity_type'])) {
            $query->where('entity_type', 'like', '%' . $filters['entity_type'] . '%');
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->paginate($perPage)->withQueryString();
    }
}
