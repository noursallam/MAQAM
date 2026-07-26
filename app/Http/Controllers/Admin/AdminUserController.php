<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(): View
    {
        $admins = Admin::with('user')->latest()->get();

        $matrix = [
            'super_admin' => ['dashboard', 'orders', 'merchants', 'qr', 'loyalty', 'commerce', 'notifications', 'risk', 'settings', 'admins'],
            'content_manager' => ['dashboard', 'qr', 'loyalty', 'commerce', 'notifications', 'settings'],
            'support' => ['dashboard', 'orders', 'merchants', 'customers', 'notifications', 'risk'],
            'finance' => ['dashboard', 'orders', 'coupons', 'loyalty', 'settings'],
        ];

        return view('admin.admins.index', compact('admins', 'matrix'));
    }
}
