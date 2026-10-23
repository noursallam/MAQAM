<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ApiException;
use App\Models\Seminar;
use App\Support\ApiPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SeminarController extends ApiController
{
    /**
     * The seminar running now, for the app's home page; null when there is none.
     * Public, but tells a signed-in customer whether they have signed up.
     */
    public function current(Request $request): JsonResponse
    {
        $seminar = Seminar::live()->orderBy('ends_at')->first();

        return $this->ok($seminar ? ApiPresenter::seminar($seminar, $request->user('sanctum')) : null);
    }

    /**
     * One seminar, with its prizes and winners. Hidden until it starts.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $seminar = Seminar::where('starts_at', '<=', now())->find($id);

        if (! $seminar) {
            throw new ApiException('NOT_FOUND', __('api.not_found'), 404);
        }

        return $this->ok(ApiPresenter::seminar($seminar, $request->user('sanctum')));
    }

    /**
     * Enters the signed-in customer into the draw. Doing it twice is harmless.
     */
    public function register(Request $request, int $id): JsonResponse
    {
        $seminar = Seminar::live()->find($id);

        if (! $seminar) {
            throw new ApiException('SEMINAR_NOT_OPEN', __('api.seminar.not_open'), 422);
        }

        $user = $request->user();
        if ($user->role === 'admin') {
            throw new ApiException('FORBIDDEN', __('api.forbidden'), 403);
        }

        $seminar->registrations()->firstOrCreate(['user_id' => $user->id]);

        return $this->ok(ApiPresenter::seminar($seminar, $user), __('api.seminar.registered'));
    }
}
