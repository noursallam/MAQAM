<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class LocaleController extends Controller
{
    public function switch(Request $request): RedirectResponse
    {
        $locale = $request->validate([
            'locale' => ['required', 'in:ar,en'],
        ])['locale'];

        Session::put('admin_locale', $locale);

        return back();
    }
}
