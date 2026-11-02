<?php

namespace App\Exports;

use App\Models\PointsTransaction;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PointsTransactionsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(
        private ?string $type = null,
        private ?string $from = null,
        private ?string $to = null,
    ) {}

    public function query()
    {
        return PointsTransaction::query()
            ->with(['customer.user', 'merchant'])
            ->when($this->type, fn ($q) => $q->where('type', $this->type))
            ->when($this->from, fn ($q) => $q->where('transaction_date', '>=', $this->from))
            ->when($this->to, fn ($q) => $q->where('transaction_date', '<=', $this->to))
            ->latest('transaction_date');
    }

    public function headings(): array
    {
        return ['العميل', 'الهاتف', 'النوع', 'المبلغ', 'الرصيد بعد', 'الوصف', 'التاجر', 'التاريخ'];
    }

    public function map($tx): array
    {
        return [
            $tx->customer?->user?->full_name,
            $tx->customer?->user?->phone_number,
            $tx->type,
            $tx->amount,
            $tx->balance_after,
            $tx->description,
            $tx->merchant?->business_name,
            ($tx->transaction_date ?? $tx->created_at)?->format('Y-m-d H:i'),
        ];
    }
}
