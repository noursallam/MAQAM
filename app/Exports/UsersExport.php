<?php

namespace App\Exports;

use App\Models\Customer;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class UsersExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(
        private ?string $governorate = null,
        private ?int $merchantId = null,
    ) {}

    public function query()
    {
        return Customer::query()
            ->with(['user.boundMerchant', 'rank'])
            ->when($this->governorate, fn ($q) => $q->whereHas('user', fn ($u) => $u->where('governorate', $this->governorate)))
            ->when($this->merchantId, fn ($q) => $q->whereHas('user', fn ($u) => $u->where('bound_merchant_id', $this->merchantId)))
            ->latest();
    }

    public function headings(): array
    {
        return ['الاسم', 'رقم الهاتف', 'المحافظة', 'المنطقة', 'التاجر', 'الرتبة', 'رصيد النقاط', 'إجمالي المكتسب', 'تاريخ التسجيل'];
    }

    public function map($customer): array
    {
        return [
            $customer->user?->full_name,
            $customer->user?->phone_number,
            $customer->user?->governorate,
            $customer->user?->area,
            $customer->user?->boundMerchant?->business_name,
            $customer->rank?->name_ar,
            $customer->points_balance,
            $customer->total_points_earned,
            $customer->created_at?->format('Y-m-d'),
        ];
    }
}
