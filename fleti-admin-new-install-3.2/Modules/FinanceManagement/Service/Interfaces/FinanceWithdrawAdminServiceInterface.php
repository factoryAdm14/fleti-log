<?php

namespace Modules\FinanceManagement\Service\Interfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Modules\UserManagement\Entities\WithdrawRequest;

interface FinanceWithdrawAdminServiceInterface
{
    public function paginateForAdmin(array $filters, int $perPage = 15): LengthAwarePaginator;

    public function findFinanceWithdraw(int|string $id): WithdrawRequest;

    public function approve(int|string $id, string $adminId, ?string $approvalNote = null): WithdrawRequest;

    public function deny(int|string $id, string $adminId, ?string $deniedNote = null): WithdrawRequest;

    public function settle(
        int|string $id,
        string $adminId,
        ?UploadedFile $receipt = null,
        ?string $existingReceiptUrl = null,
    ): WithdrawRequest;

    public function retryPixPayout(int|string $id, string $adminId): WithdrawRequest;
}
