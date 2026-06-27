<?php

namespace Modules\FinanceManagement\Service\Interfaces;

use Modules\FinanceManagement\Entities\FinanceSetting;

interface FinanceSettingServiceInterface
{
    public function get(): FinanceSetting;

    public function update(array $data): FinanceSetting;
}
