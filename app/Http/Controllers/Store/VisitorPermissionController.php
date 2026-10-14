<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use App\Services\Geo\LocationRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * What the browser answered when the signed-in customer was asked for location and notifications.
 */
class VisitorPermissionController extends Controller
{
    private const STATES = ['granted', 'denied', 'prompt', 'unsupported'];

    public function store(Request $request, LocationRecorder $recorder): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'location_permission' => ['required', Rule::in(self::STATES)],
            'notification_permission' => ['required', Rule::in(self::STATES)],
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0'],
            'push_token' => ['nullable', 'string', 'max:512'],
        ]);

        // Called from fetch(), so answer in JSON rather than the web routes' redirect-back
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        $user = $request->user();

        $user->forceFill([
            'location_permission' => $validated['location_permission'],
            'notification_permission' => $validated['notification_permission'],
            'permissions_reported_at' => now(),
        ])->save();

        $location = null;

        // Coordinates only count when the browser actually granted them
        if ($validated['location_permission'] === 'granted' && isset($validated['latitude'], $validated['longitude'])) {
            $location = $recorder->record(
                $user,
                (float) $validated['latitude'],
                (float) $validated['longitude'],
                isset($validated['accuracy']) ? (int) min($validated['accuracy'], 4_000_000) : null,
            );
        }

        if ($validated['notification_permission'] === 'granted' && ! empty($validated['push_token'])) {
            DeviceToken::register($user, $validated['push_token'], 'web');
        }

        return response()->json([
            'saved' => true,
            'location_id' => $location?->id,
        ]);
    }
}
