<?php

namespace App\Jobs;

use App\Models\DeviceToken;
use App\Services\Push\FcmService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SendPushNotification implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;

    /** Cache key of the last finished push, shown on the dashboard. */
    public const LAST_RUN = 'push.last_run';

    public int $timeout = 300;

    // A retry would push the same notification twice to users already reached
    public int $tries = 1;

    /**
     * @param  list<int>  $userIds
     */
    public function __construct(
        public array $userIds,
        public string $title,
        public string $body,
        public string $type,
        public array $data = [],
    ) {}

    public function handle(FcmService $fcm): void
    {
        $tokens = DeviceToken::whereIn('user_id', $this->userIds)
            ->whereHas('user', fn ($q) => $q->where('is_active', true))
            ->pluck('token')
            ->all();

        if (! $tokens) {
            return;
        }

        $results = $fcm->sendMany($tokens, $this->title, $this->body, $this->data + ['type' => $this->type]);
        $counts = array_count_values($results);

        $dead = array_keys($results, FcmService::INVALID_TOKEN, true);
        if ($dead) {
            DeviceToken::whereIn('token', $dead)->delete();
        }

        $summary = [
            'title' => $this->title,
            'devices' => count($tokens),
            'sent' => $counts[FcmService::SENT] ?? 0,
            'failed' => $counts[FcmService::FAILED] ?? 0,
            'removed' => count($dead),
            'finished_at' => now()->toIso8601String(),
        ];
        Cache::forever(self::LAST_RUN, $summary);
        Log::info('FCM: push finished', $summary);
    }
}
