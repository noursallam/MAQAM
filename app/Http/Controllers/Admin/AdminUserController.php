<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Support\AdminAccess;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(): View
    {
        $admins = Admin::with('user')->latest()->get();
        $matrix = AdminAccess::matrix();

        return view('admin.admins.index', compact('admins', 'matrix'));
    }
}
