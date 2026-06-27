<?php

namespace Modules\TransactionManagement\Service;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Gateways\Entities\PaymentRequest;

class PixTransactionService
{
    public const PIX_METHODS = ['efi_pix', 'mercadopago_pix'];

    public function index(array $criteria = [], ?int $limit = null, ?int $offset = null): LengthAwarePaginator
    {
        return $this->baseQuery($criteria)
            ->paginate($limit ?? paginationLimit(), ['*'], 'page', $offset ?? 1)
            ->appends($criteria);
    }

    public function export(array $criteria = []): Collection
    {
        return $this->baseQuery($criteria)
            ->get()
            ->map(fn (PaymentRequest $payment) => $this->mapExportRow($payment));
    }

    public function summary(array $criteria = []): array
    {
        $rows = $this->baseQuery($criteria)->get(['payment_amount', 'is_paid', 'payment_method']);

        return [
            'total' => $rows->count(),
            'paid_count' => $rows->where('is_paid', 1)->count(),
            'pending_count' => $rows->where('is_paid', 0)->count(),
            'paid_amount' => round((float) $rows->where('is_paid', 1)->sum('payment_amount'), 2),
            'efi_count' => $rows->where('payment_method', 'efi_pix')->count(),
            'mercadopago_count' => $rows->where('payment_method', 'mercadopago_pix')->count(),
        ];
    }

    protected function baseQuery(array $criteria = []): Builder
    {
        $query = PaymentRequest::query()
            ->with(['payer'])
            ->whereIn('payment_method', self::PIX_METHODS)
            ->orderByDesc('created_at');

        if (! empty($criteria['search'])) {
            $search = $criteria['search'];
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('id', 'like', "%{$search}%")
                    ->orWhere('transaction_id', 'like', "%{$search}%")
                    ->orWhere('attribute_id', 'like', "%{$search}%")
                    ->orWhereHas('payer', function (Builder $payerQuery) use ($search) {
                        $payerQuery->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        if (! empty($criteria['gateway']) && $criteria['gateway'] !== 'all') {
            $query->where('payment_method', $criteria['gateway']);
        }

        if (! empty($criteria['context']) && $criteria['context'] !== 'all') {
            $query->where('attribute', $criteria['context']);
        }

        if (isset($criteria['is_paid']) && $criteria['is_paid'] !== 'all' && $criteria['is_paid'] !== '') {
            $query->where('is_paid', $criteria['is_paid'] === 'paid' ? 1 : 0);
        }

        if (! empty($criteria['date_range']) && $criteria['date_range'] !== ALL_TIME) {
            $dateInput = $criteria['date_range'] === 'custom_date'
                ? ['start' => $criteria['start'] ?? null, 'end' => $criteria['end'] ?? null]
                : $criteria['date_range'];
            $date = getDateRange($dateInput);
            $query->whereBetween('created_at', [$date['start'], $date['end']]);
        }

        return $query;
    }

    public function gatewayLabel(?string $method): string
    {
        return match ($method) {
            'efi_pix' => translate('efi_pix'),
            'mercadopago_pix' => translate('mercadopago_pix'),
            default => $method ?? translate('N/A'),
        };
    }

    public function contextLabel(?string $attribute): string
    {
        return match ($attribute) {
            'add_wallet_amount_digitally' => translate('wallet_top_up'),
            'order' => translate('trip_payment'),
            default => $attribute ? ucwords(str_replace('_', ' ', $attribute)) : translate('N/A'),
        };
    }

    public function statusLabel(PaymentRequest $payment): string
    {
        if ($payment->is_paid) {
            return translate('paid');
        }

        $pixStatus = $this->pixPayload($payment)['status'] ?? null;

        return $pixStatus ? ucwords(str_replace('_', ' ', (string) $pixStatus)) : translate('pending');
    }

    public function pixPayload(PaymentRequest $payment): array
    {
        $additional = $payment->additional_data;
        if (is_string($additional)) {
            $additional = json_decode($additional, true) ?? [];
        }

        return is_array($additional['pix'] ?? null) ? $additional['pix'] : [];
    }

    protected function mapExportRow(PaymentRequest $payment): array
    {
        return [
            'Payment ID' => $payment->id,
            'Date' => date('d-m-Y h:i A', strtotime($payment->created_at)),
            'Gateway' => $this->gatewayLabel($payment->payment_method),
            'External TX ID' => $payment->transaction_id ?? translate('N/A'),
            'Customer' => trim(($payment->payer?->first_name ?? '') . ' ' . ($payment->payer?->last_name ?? '')) ?: translate('N/A'),
            'Context' => $this->contextLabel($payment->attribute),
            'Reference' => $payment->attribute_id ?? translate('N/A'),
            'Amount' => getCurrencyFormat($payment->payment_amount),
            'Status' => $this->statusLabel($payment),
        ];
    }
}
