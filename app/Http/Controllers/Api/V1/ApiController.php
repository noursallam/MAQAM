<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Rank;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class ApiController extends Controller
{
    protected function ok(mixed $data = null, string $message = '', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    /**
     * @param  callable(mixed): mixed  $map
     */
    protected function paginated(LengthAwarePaginator $page, callable $map): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => '',
            'data' => collect($page->items())->map($map)->values(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    protected function perPage(Request $request, int $default = 20): int
    {
        return min(50, max(1, (int) $request->integer('per_page', $default)));
    }

    /**
     * The loyalty profile of the signed-in user (created on first use for legacy accounts).
     */
    protected function customer(Request $request): Customer
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->role === 'admin') {
            throw new ApiException('FORBIDDEN', __('api.forbidden'), 403);
        }

        return $user->customer()->firstOrCreate([], [
            'rank_id' => Rank::where('is_active', true)->orderBy('min_points')->value('id'),
            'points_balance' => 0,
            'total_points_earned' => 0,
            'total_points_spent' => 0,
        ]);
    }
}
