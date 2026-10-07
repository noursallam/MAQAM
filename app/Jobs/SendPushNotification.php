<?php

namespace App\Jobs;

use App\Models\DeviceToken;
use App\Services\Push\FcmService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;

class SendPushNotification implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;

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
        DeviceToken::whereIn('user_id', $this->userIds)
            ->whereHas('user', fn ($q) => $q->where('is_active', true))
            ->get()
            ->each(function (DeviceToken $device) use ($fcm) {
                $result = $fcm->send($device->token, $this->title, $this->body, $this->data + ['type' => $this->type]);

                if ($result === FcmService::INVALID_TOKEN) {
                    $device->delete();
                }
            });
    }
}
