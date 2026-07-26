<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\Customer;
use App\Models\Rank;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(): View
    {
        return view('admin.notifications.index', [
            'notifications' => AppNotification::with('user')->latest()->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.notifications.create', [
            'ranks' => Rank::where('is_active', true)->orderBy('min_points')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'body_en' => ['nullable', 'string'],
            'type' => ['required', 'in:rank_upgrade,offer,reminder,order_update,promotion'],
            'segment' => ['required', 'in:all,customers,merchants,rank,inactive,new,active'],
            'rank_id' => ['nullable', 'exists:ranks,id'],
        ]);

        $users = match ($data['segment']) {
            'customers' => User::where('role', 'customer')->where('is_active', true)->get(),
            'merchants' => User::where('role', 'merchant')->where('is_active', true)->get(),
            'rank' => User::where('role', 'customer')->where('is_active', true)
                ->whereHas('customer', fn ($c) => $c->where('rank_id', $data['rank_id']))->get(),
            'inactive' => User::where('role', 'customer')->where(function ($q) {
                $q->where('is_active', false)
                    ->orWhere('last_login_at', '<', now()->subDays(30))
                    ->orWhereNull('last_login_at');
            })->get(),
            'new' => User::where('role', 'customer')->where('created_at', '>=', now()->subDays(14))->get(),
            'active' => User::where('role', 'customer')->where('is_active', true)
                ->where('last_login_at', '>=', now()->subDays(14))->get(),
            default => User::whereIn('role', ['customer', 'merchant'])->where('is_active', true)->get(),
        };

        $rows = $users->map(fn (User $user) => [
            'user_id' => $user->id,
            'title' => $data['title'],
            'body' => $data['body'],
            'type' => $data['type'],
            'is_read' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ])->all();

        foreach (array_chunk($rows, 500) as $chunk) {
            AppNotification::insert($chunk);
        }

        return redirect()->route('admin.notifications.index')
            ->with('success', __('admin.notifications.sent').': '.count($rows));
    }
}
