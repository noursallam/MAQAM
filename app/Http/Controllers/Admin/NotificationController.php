<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendPushNotification;
use App\Models\AppNotification;
use App\Models\DeviceToken;
use App\Models\Rank;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\Push\FcmService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /** A push waiting longer than this means nothing is working the queue. */
    private const STUCK_AFTER_MINUTES = 3;

    public function index(FcmService $fcm): View
    {
        return view('admin.notifications.index', [
            'notifications' => AppNotification::with('user')->latest()->paginate(20),
            'health' => $this->health($fcm),
        ]);
    }

    public function create(): View
    {
        return view('admin.notifications.create', [
            'ranks' => Rank::where('is_active', true)->orderBy('min_points')->get(),
        ]);
    }

    public function store(Request $request, NotificationService $notifications): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:2000'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'body_en' => ['nullable', 'string', 'max:2000'],
            'type' => ['required', 'in:rank_upgrade,offer,reminder,order_update,promotion'],
            'segment' => ['required', 'in:all,customers,merchants,rank,inactive,new,active,phone'],
            'rank_id' => ['nullable', 'required_if:segment,rank', 'exists:ranks,id'],
            'phone' => ['nullable', 'required_if:segment,phone', 'string', 'max:20'],
        ]);

        // Only what is needed to choose the language: a broadcast can reach every customer
        $users = $this->audience($data)->get(['id', 'preferred_language']);

        if ($data['segment'] === 'phone' && $users->isEmpty()) {
            throw ValidationException::withMessages(['phone' => __('admin.notifications.phone_not_found')]);
        }

        // Users who chose English get the English text when the admin wrote one
        $hasEnglish = filled($data['title_en'] ?? null) && filled($data['body_en'] ?? null);
        [$english, $arabic] = $users->partition(fn (User $user) => $hasEnglish && $user->preferred_language === 'en');

        $notifications->notify($arabic->pluck('id')->all(), $data['title'], $data['body'], $data['type']);
        $notifications->notify($english->pluck('id')->all(), (string) ($data['title_en'] ?? ''), (string) ($data['body_en'] ?? ''), $data['type']);

        $devices = DeviceToken::whereIn('user_id', $users->pluck('id'))->count();

        return redirect()->route('admin.notifications.index')->with('success', __('admin.notifications.sent_summary', [
            'users' => $users->count(),
            'devices' => $devices,
        ]));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function audience(array $data): Builder
    {
        $customers = fn () => User::where('role', 'customer');

        return match ($data['segment']) {
            'customers' => $customers()->where('is_active', true),
            // Customers who applied in the app keep the customer role, so a merchant is anyone with an approved profile
            'merchants' => User::where('is_active', true)
                ->whereHas('merchant', fn ($m) => $m->where('is_approved', true)),
            'rank' => $customers()->where('is_active', true)
                ->whereHas('customer', fn ($c) => $c->where('rank_id', $data['rank_id'])),
            'inactive' => $customers()->where(function ($q) {
                $q->where('is_active', false)
                    ->orWhere('last_login_at', '<', now()->subDays(30))
                    ->orWhereNull('last_login_at');
            }),
            'new' => $customers()->where('created_at', '>=', now()->subDays(14)),
            'active' => $customers()->where('is_active', true)->where('last_login_at', '>=', now()->subDays(14)),
            'phone' => User::where('is_active', true)->whereIn('role', ['customer', 'merchant'])
                ->where('phone_number', $this->normalizePhone((string) $data['phone'])),
            default => User::whereIn('role', ['customer', 'merchant'])->where('is_active', true),
        };
    }

    /**
     * Whether push can reach phones right now, for the panel above the history.
     *
     * @return array<string, mixed>
     */
    private function health(FcmService $fcm): array
    {
        $waiting = DB::table('jobs')->where('queue', 'push');
        $oldest = (clone $waiting)->min('created_at');
        $oldestAt = $oldest ? Carbon::createFromTimestamp((int) $oldest) : null;
        $last = Cache::get(SendPushNotification::LAST_RUN);

        return [
            'configured' => $fcm->isConfigured(),
            'devices' => DeviceToken::count(),
            'users_with_devices' => DeviceToken::distinct()->count('user_id'),
            'waiting' => (clone $waiting)->count(),
            'oldest_waiting' => $oldestAt,
            'stuck' => $oldestAt !== null && $oldestAt->lt(now()->subMinutes(self::STUCK_AFTER_MINUTES)),
            'failed_jobs' => DB::table('failed_jobs')->where('queue', 'push')->count(),
            'last_run' => $last,
            'last_run_at' => $last ? Carbon::parse($last['finished_at']) : null,
        ];
    }

    /**
     * `+20 10 1234 5678` -> `01012345678`, the way numbers are stored.
     */
    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);
        $digits = str_starts_with($digits, '20') ? substr($digits, 2) : $digits;

        return str_starts_with($digits, '0') ? $digits : '0'.$digits;
    }
}
