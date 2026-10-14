<?php

namespace App\Services\Geo;

use App\Models\UserLocation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

/**
 * Turns coordinates into country / governorate / city using OpenStreetMap Nominatim.
 * Their usage policy allows one request per second with an identifying User-Agent.
 */
class ReverseGeocoder
{
    public const MAX_ATTEMPTS = 5;

    /**
     * Fill in the place names of a stored location. Returns false when it should be retried later.
     */
    public function geocode(UserLocation $location): bool
    {
        if ($location->geocoded_at) {
            return true;
        }

        // ~110 m grid: nearby customers share one lookup
        $key = sprintf('geo.reverse.%.3f,%.3f', $location->latitude, $location->longitude);
        $place = Cache::get($key);

        if ($place === null) {
            if (! RateLimiter::attempt('nominatim', 1, fn () => true, 1)) {
                return false;
            }

            $place = $this->lookup($location->latitude, $location->longitude);

            if ($place === null) {
                $location->increment('geocode_attempts');

                return false;
            }

            Cache::put($key, $place, now()->addDays(30));
        }

        $location->forceFill($place + ['geocoded_at' => now()])->save();

        return true;
    }

    /**
     * @return array{country_code: ?string, country_name: ?string, region_code: ?string, region_name: ?string, city: ?string}|null
     */
    private function lookup(float $latitude, float $longitude): ?array
    {
        try {
            $response = Http::withHeaders(['User-Agent' => config('services.geocoding.user_agent')])
                ->timeout(8)
                ->get(rtrim((string) config('services.geocoding.url'), '/').'/reverse', array_filter([
                    'format' => 'jsonv2',
                    'lat' => $latitude,
                    'lon' => $longitude,
                    'zoom' => 10,
                    'addressdetails' => 1,
                    // Stored in English so grouping is stable; the admin panel translates by code
                    'accept-language' => 'en',
                    'email' => config('services.geocoding.email'),
                ]));
        } catch (Throwable $e) {
            Log::warning('Reverse geocoding failed', ['error' => $e->getMessage()]);

            return null;
        }

        if (! $response->successful()) {
            Log::warning('Reverse geocoding failed', ['http_status' => $response->status()]);

            return null;
        }

        $address = (array) $response->json('address', []);

        // Open sea or an unmapped spot: nothing more to learn, don't retry
        if ($address === []) {
            return ['country_code' => null, 'country_name' => null, 'region_code' => null, 'region_name' => null, 'city' => null];
        }

        $regionName = $address['state'] ?? $address['province'] ?? $address['region'] ?? null;

        return [
            'country_code' => isset($address['country_code']) ? strtoupper(substr($address['country_code'], 0, 2)) : null,
            'country_name' => $this->clip($address['country'] ?? null),
            'region_code' => $this->clip($address['ISO3166-2-lvl4'] ?? $address['ISO3166-2-lvl3'] ?? null, 10),
            'region_name' => $this->clip($regionName ? trim(preg_replace('/\s+Governorate$/i', '', $regionName)) : null),
            'city' => $this->clip($address['city'] ?? $address['town'] ?? $address['village'] ?? $address['suburb'] ?? $address['county'] ?? null),
        ];
    }

    private function clip(?string $value, int $length = 100): ?string
    {
        return $value === null ? null : mb_substr($value, 0, $length);
    }
}
