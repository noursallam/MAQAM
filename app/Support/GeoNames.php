<?php

namespace App\Support;

use Locale;

/**
 * Display names for countries and Egyptian governorates in the current locale.
 */
class GeoNames
{
    /**
     * ISO 3166-2 codes of Egypt's 27 governorates.
     *
     * @var array<string, array{ar: string, en: string}>
     */
    public const EGYPT_GOVERNORATES = [
        'EG-C' => ['ar' => 'القاهرة', 'en' => 'Cairo'],
        'EG-GZ' => ['ar' => 'الجيزة', 'en' => 'Giza'],
        'EG-ALX' => ['ar' => 'الإسكندرية', 'en' => 'Alexandria'],
        'EG-KB' => ['ar' => 'القليوبية', 'en' => 'Qalyubia'],
        'EG-DK' => ['ar' => 'الدقهلية', 'en' => 'Dakahlia'],
        'EG-SHR' => ['ar' => 'الشرقية', 'en' => 'Sharqia'],
        'EG-GH' => ['ar' => 'الغربية', 'en' => 'Gharbia'],
        'EG-MNF' => ['ar' => 'المنوفية', 'en' => 'Monufia'],
        'EG-BH' => ['ar' => 'البحيرة', 'en' => 'Beheira'],
        'EG-KFS' => ['ar' => 'كفر الشيخ', 'en' => 'Kafr El Sheikh'],
        'EG-DT' => ['ar' => 'دمياط', 'en' => 'Damietta'],
        'EG-PTS' => ['ar' => 'بورسعيد', 'en' => 'Port Said'],
        'EG-IS' => ['ar' => 'الإسماعيلية', 'en' => 'Ismailia'],
        'EG-SUZ' => ['ar' => 'السويس', 'en' => 'Suez'],
        'EG-FYM' => ['ar' => 'الفيوم', 'en' => 'Faiyum'],
        'EG-BNS' => ['ar' => 'بني سويف', 'en' => 'Beni Suef'],
        'EG-MN' => ['ar' => 'المنيا', 'en' => 'Minya'],
        'EG-AST' => ['ar' => 'أسيوط', 'en' => 'Asyut'],
        'EG-SHG' => ['ar' => 'سوهاج', 'en' => 'Sohag'],
        'EG-KN' => ['ar' => 'قنا', 'en' => 'Qena'],
        'EG-LX' => ['ar' => 'الأقصر', 'en' => 'Luxor'],
        'EG-ASN' => ['ar' => 'أسوان', 'en' => 'Aswan'],
        'EG-BA' => ['ar' => 'البحر الأحمر', 'en' => 'Red Sea'],
        'EG-WAD' => ['ar' => 'الوادي الجديد', 'en' => 'New Valley'],
        'EG-MT' => ['ar' => 'مطروح', 'en' => 'Matrouh'],
        'EG-SIN' => ['ar' => 'شمال سيناء', 'en' => 'North Sinai'],
        'EG-JS' => ['ar' => 'جنوب سيناء', 'en' => 'South Sinai'],
    ];

    public static function country(?string $code, ?string $fallback = null): ?string
    {
        if (! $code) {
            return $fallback;
        }

        if (class_exists(Locale::class)) {
            $name = Locale::getDisplayRegion('-'.strtoupper($code), app()->getLocale());

            // intl echoes the code back when it has no name for it
            if ($name !== '' && strcasecmp($name, $code) !== 0) {
                return $name;
            }
        }

        return $fallback ?? strtoupper($code);
    }

    public static function region(?string $code, ?string $fallback = null): ?string
    {
        $locale = app()->getLocale() === 'ar' ? 'ar' : 'en';

        return self::EGYPT_GOVERNORATES[$code][$locale] ?? $fallback ?? $code;
    }

    public static function flag(?string $code): string
    {
        if (! $code || strlen($code) !== 2) {
            return '';
        }

        return mb_chr(0x1F1E6 + ord(strtoupper($code[0])) - 65).mb_chr(0x1F1E6 + ord(strtoupper($code[1])) - 65);
    }
}
