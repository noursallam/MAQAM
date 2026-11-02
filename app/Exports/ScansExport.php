<?php

namespace App\Exports;

use App\Models\QrScan;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ScansExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(
        private ?string $from = null,
        private ?string $to = null,
        private ?int $merchantId = null,
    ) {}

    public function query()
    {
        return QrScan::query()
            ->with(['customer.user', 'merchant', 'qrCode'])
            ->when($this->from, fn ($q) => $q->where('scanned_at', '>=', $this->from))
            ->when($this->to, fn ($q) => $q->where('scanned_at', '<=', $this->to))
            ->when($this->merchantId, fn ($q) => $q->where('merchant_id', $this->merchantId))
            ->latest('scanned_at');
    }

    public function headings(): array
    {
        return ['العميل', 'الهاتف', 'الكود', 'التاجر', 'نقاط العميل', 'نقاط التاجر', 'تاريخ السكان'];
    }

    public function map($scan): array
    {
        return [
            $scan->customer?->user?->full_name,
            $scan->customer?->user?->phone_number,
            $scan->qrCode?->serial_code,
            $scan->merchant?->business_name,
            $scan->points_awarded_customer,
            $scan->points_awarded_merchant,
            $scan->scanned_at?->format('Y-m-d H:i'),
        ];
    }
}
