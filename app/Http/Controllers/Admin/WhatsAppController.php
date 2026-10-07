<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\WhatsApp\SenderBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Throwable;

class WhatsAppController extends Controller
{
    public function __construct(
        protected SenderBotService $senderBot
    ) {}

    public function index(): View
    {
        return view('admin.whatsapp.index', [
            'configured' => $this->senderBot->isConfigured(),
        ]);
    }

    public function status(): JsonResponse
    {
        try {
            return response()->json(['ok' => true] + $this->senderBot->status());
        } catch (Throwable $e) {
            return $this->unreachable($e);
        }
    }

    public function connect(): JsonResponse
    {
        try {
            return response()->json(['ok' => true, 'qr' => $this->senderBot->startPairing()]);
        } catch (Throwable $e) {
            return $this->unreachable($e);
        }
    }

    public function disconnect(): JsonResponse
    {
        try {
            $this->senderBot->disconnect();
            Cache::forget('senderbot.linked_phone');

            return response()->json(['ok' => true]);
        } catch (Throwable $e) {
            return $this->unreachable($e);
        }
    }

    private function unreachable(Throwable $e): JsonResponse
    {
        report($e);

        return response()->json(['ok' => false, 'message' => $e->getMessage()], 502);
    }
}
