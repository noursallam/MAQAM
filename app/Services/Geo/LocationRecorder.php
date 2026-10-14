<?php

namespace App\Services\Geo;

use App\Models\User;
use App\Models\UserLocation;

use function Illuminate\Support\defer;

class LocationRecorder
{
    // A fix this close to the last one, this soon after it, is the same visit
    private const SAME_PLACE_METRES = 250;
    private const SAME_PLACE_HOURS = 12;

    public function __construct(private ReverseGeocoder $geocoder) {}

    public function record(User $user, float $latitude, float $longitude, ?int $accuracy = null, string $source = 'web'): UserLocation
    {
        $latest = $user->latestLocation;

        if ($latest
            && $latest->created_at->gt(now()->subHours(self::SAME_PLACE_HOURS))
            && $latest->distanceTo($latitude, $longitude) <= self::SAME_PLACE_METRES) {
            $latest->touch();

            return $latest;
        }

        $location = $user->locations()->create([
            'latitude' => round($latitude, 7),
            'longitude' => round($longitude, 7),
            'accuracy_m' => $accuracy,
            'source' => $source,
        ]);

        $user->forceFill(['latest_location_id' => $location->id])->save();
        $user->setRelation('latestLocation', $location);

        // After the response is sent, so the visitor never waits on the map service.
        // Misses are picked up by the locations:geocode command.
        defer(fn () => $this->geocoder->geocode($location));

        return $location;
    }
}
