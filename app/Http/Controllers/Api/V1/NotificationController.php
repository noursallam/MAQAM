<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\AppNotification;
use App\Support\ApiPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = AppNotification::where('user_id', $request->user()->id);

        $page = (clone $query)->latest('id')->paginate($this->perPage($request));

        $response = $this->paginated($page, ApiPresenter::notification(...));
        $body = $response->getData(true);
        $body['meta']['unread_count'] = (clone $query)->where('is_read', false)->count();

        return $response->setData($body);
    }

    public function markRead(Request $request, int $id): JsonResponse
    {
        $notification = AppNotification::where('user_id', $request->user()->id)->findOrFail($id);

        if (! $notification->is_read) {
            $notification->update(['is_read' => true, 'read_at' => now()]);
        }

        return $this->ok(ApiPresenter::notification($notification));
    }

    public function markAllRead(Request $request): JsonResponse
    {
        AppNotification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        return $this->ok(null, __('api.saved'));
    }
}
