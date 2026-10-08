<?php

namespace App\Services;

use App\Jobs\SendPushNotification;
use App\Models\AppNotification;
use App\Models\User;
use App\Services\Push\FcmService;
use Closure;

class NotificationService
{
    public function __construct(
        protected FcmService $fcm
    ) {}

    /**
     * Store an in-app notification for each user and push it to their devices.
     *
     * @param  list<int>  $userIds
     */
    public function notify(array $userIds, string $title, string $body, string $type, array $data = []): void
    {
        $now = now();

        foreach (array_chunk($userIds, 500) as $chunk) {
            AppNotification::insert(array_map(fn ($id) => [
                'user_id' => $id,
                'title' => $title,
                'body' => $body,
                'type' => $type,
                // A bulk insert bypasses the model's cast, so encode by hand
                'data' => $data ? json_encode($data) : null,
                'is_read' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ], $chunk));
        }

        $this->push($userIds, $title, $body, $type, $data);
    }

    /**
     * Notify each user in their own language.
     *
     * @param  list<int>  $userIds
     * @param  array<string, string|Closure(string): string>  $replace  a closure receives the locale ('ar' or 'en')
     */
    public function notifyTranslated(array $userIds, string $titleKey, string $bodyKey, array $replace, string $type, array $data = []): void
    {
        User::whereIn('id', $userIds)->pluck('preferred_language', 'id')
            ->groupBy(fn ($language) => $language === 'en' ? 'en' : 'ar', preserveKeys: true)
            ->each(function ($users, string $locale) use ($titleKey, $bodyKey, $replace, $type, $data) {
                $values = array_map(fn ($value) => $value instanceof Closure ? $value($locale) : $value, $replace);

                $this->notify(
                    $users->keys()->all(),
                    __($titleKey, $values, $locale),
                    __($bodyKey, $values, $locale),
                    $type,
                    $data,
                );
            });
    }

    /**
     * Push only (the in-app rows already exist).
     *
     * @param  list<int>  $userIds
     */
    public function push(array $userIds, string $title, string $body, string $type, array $data = []): void
    {
        if (! $userIds || ! $this->fcm->isConfigured()) {
            return;
        }

        foreach (array_chunk($userIds, 200) as $chunk) {
            SendPushNotification::dispatch($chunk, $title, $body, $type, $data)->onQueue('push')->afterCommit();
        }
    }
}
