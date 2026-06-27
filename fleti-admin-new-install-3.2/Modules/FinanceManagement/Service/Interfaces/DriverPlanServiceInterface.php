<?php

namespace Modules\FinanceManagement\Service\Interfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\FinanceManagement\Entities\DriverPlan;

interface DriverPlanServiceInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    /**
     * @return \Illuminate\Support\Collection<int, DriverPlan>
     */
    public function listActive(): \Illuminate\Support\Collection;

    public function find(string $id): DriverPlan;

    public function create(array $data): DriverPlan;

    public function update(string $id, array $data): DriverPlan;

    public function toggleActive(string $id): DriverPlan;
}
