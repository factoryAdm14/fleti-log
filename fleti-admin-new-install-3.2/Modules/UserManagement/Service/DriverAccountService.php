<?php

namespace Modules\UserManagement\Service;

use App\Service\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\UserManagement\Repository\UserAccountRepositoryInterface;
use Modules\UserManagement\Service\Interfaces\DriverAccountServiceInterface;

class DriverAccountService extends BaseService implements DriverAccountServiceInterface
{
    protected $userAccountRepository;

    public function __construct(UserAccountRepositoryInterface $userAccountRepository)
    {
        parent::__construct($userAccountRepository);
        $this->userAccountRepository = $userAccountRepository;
    }

    public function export(Collection $data): Collection|LengthAwarePaginator|\Illuminate\Support\Collection
    {
        return $data->map(function ($item) {
            return [
                'Id' => $item['id'],
                'Transaction Id' => $item['id'],
                'Reference' => $item['reference'],
                'Transaction Date' => $item['created_at']->format('d-m-Y h:i A'),
                'Transaction To' => trim(($item->user?->first_name ?? '') . ' ' . ($item->user?->last_name ?? '')),
                'Debit' => getCurrencyFormat($item['debit']),
                'Credit' => getCurrencyFormat($item['credit']),
                'Balance' => getCurrencyFormat($item['balance']),
            ];
        });
    }

    public function updateManyWithIncrement(array $ids, $column, $amount = 0)
    {
        $this->userAccountRepository->updateManyWithIncrement($ids, $column, $amount);
    }
}
