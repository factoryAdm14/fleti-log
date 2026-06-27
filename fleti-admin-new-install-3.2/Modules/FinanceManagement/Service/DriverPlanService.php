<?php

namespace Modules\FinanceManagement\Service;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\FinanceManagement\Entities\DriverPlan;
use Modules\FinanceManagement\Service\Interfaces\DriverPlanServiceInterface;

class DriverPlanService implements DriverPlanServiceInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return DriverPlan::query()
            ->withCount('subscriptions')
            ->orderBy('price')
            ->paginate($perPage);
    }

    public function listActive(): \Illuminate\Support\Collection
    {
        return DriverPlan::query()
            ->where('is_active', true)
            ->orderBy('price')
            ->get();
    }

    public function find(string $id): DriverPlan
    {
        return DriverPlan::query()->findOrFail($id);
    }

    public function create(array $data): DriverPlan
    {
        return DriverPlan::query()->create($this->normalizeData($data));
    }

    public function update(string $id, array $data): DriverPlan
    {
        $plan = $this->find($id);
        $plan->update($this->normalizeData($data));

        return $plan->fresh();
    }

    public function toggleActive(string $id): DriverPlan
    {
        $plan = $this->find($id);
        $plan->update(['is_active' => !$plan->is_active]);

        return $plan->fresh();
    }

    private function normalizeData(array $data): array
    {
        if (isset($data['benefits']) && is_string($data['benefits'])) {
            $lines = array_filter(array_map('trim', explode("\n", $data['benefits'])));
            $data['benefits'] = $lines ?: null;
        }

        $data['is_active'] = (bool) ($data['is_active'] ?? true);
        $data['commission_percentage'] = (float) ($data['commission_percentage'] ?? 0);

        return $data;
    }
}
