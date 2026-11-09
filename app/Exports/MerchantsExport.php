<?php

namespace App\Exports;

use App\Models\Merchant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MerchantsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function query(): Builder|EloquentBuilder|Relation
    {
        return Merchant::query()->with('user')->latest()->orderBy('id');
    }

    public function headings(): array
    {
        return ['اسم البيزنس', 'الكود', 'صاحب الحساب', 'الهاتف', 'معتمد', 'عدد الفنيين', 'إجمالي السكانات', 'إجمالي النقاط', 'تاريخ التسجيل'];
    }

    public function map($merchant): array
    {
        return [
            $merchant->business_name,
            $merchant->merchant_code,
            $merchant->user?->full_name,
            $merchant->user?->phone_number,
            $merchant->is_approved ? 'نعم' : 'لا',
            User::where('bound_merchant_id', $merchant->id)->count(),
            $merchant->qrScans()->count(),
            (int) $merchant->qrScans()->sum('points_awarded_merchant'),
            $merchant->created_at?->format('Y-m-d'),
        ];
    }
}
