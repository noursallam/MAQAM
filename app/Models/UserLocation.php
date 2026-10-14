<?php

namespace App\Models;

use App\Support\GeoNames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLocation extends Model
{
    protected $fillable = [
        'user_id', 'latitude', 'longitude', 'accuracy_m', 'source',
        'country_code', 'country_name', 'region_code', 'region_name', 'city',
        'geocoded_at', 'geocode_attempts',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'accuracy_m' => 'integer',
            'geocode_attempts' => 'integer',
            'geocoded_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function countryLabel(): ?string
    {
        return GeoNames::country($this->country_code, $this->country_name);
    }

    public function regionLabel(): ?string
    {
        return GeoNames::region($this->region_code, $this->region_name);
    }

    public function mapUrl(): string
    {
        return 'https://www.google.com/maps?q='.$this->latitude.','.$this->longitude;
    }

    /**
     * Distance to another point in metres (haversine).
     */
    public function distanceTo(float $latitude, float $longitude): float
    {
        $rad = M_PI / 180;
        $dLat = ($latitude - $this->latitude) * $rad;
        $dLng = ($longitude - $this->longitude) * $rad;
        $a = sin($dLat / 2) ** 2 + cos($this->latitude * $rad) * cos($latitude * $rad) * sin($dLng / 2) ** 2;

        return 6371000 * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
