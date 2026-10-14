<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class WebPushController extends Controller
{
    public const FIREBASE_VERSION = '10.12.2';

    /**
     * The Firebase web config, or null until all of it is set in .env.
     *
     * @return array{apiKey: string, projectId: string, messagingSenderId: string, appId: string, vapidKey: string}|null
     */
    public static function config(): ?array
    {
        $web = (array) config('services.firebase.web');

        $config = [
            'apiKey' => (string) ($web['api_key'] ?? ''),
            'projectId' => (string) ($web['project_id'] ?? ''),
            'messagingSenderId' => (string) ($web['messaging_sender_id'] ?? ''),
            'appId' => (string) ($web['app_id'] ?? ''),
            'vapidKey' => (string) ($web['vapid_key'] ?? ''),
        ];

        return in_array('', $config, true) ? null : $config;
    }

    /**
     * Shows pushes that arrive while no MAQAM tab is focused.
     */
    public function serviceWorker(): Response
    {
        $config = self::config();

        if (! $config) {
            abort(404);
        }

        unset($config['vapidKey']);
        $version = self::FIREBASE_VERSION;
        $json = json_encode($config, JSON_UNESCAPED_SLASHES);

        $script = <<<JS
            importScripts('https://www.gstatic.com/firebasejs/{$version}/firebase-app-compat.js');
            importScripts('https://www.gstatic.com/firebasejs/{$version}/firebase-messaging-compat.js');
            firebase.initializeApp({$json});
            // Notification payloads are displayed by Firebase itself; this keeps the worker registered for them
            firebase.messaging();
            JS;

        return response($script, 200, [
            'Content-Type' => 'application/javascript; charset=utf-8',
            'Cache-Control' => 'no-cache',
            'Service-Worker-Allowed' => '/',
        ]);
    }
}
