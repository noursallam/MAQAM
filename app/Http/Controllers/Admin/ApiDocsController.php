<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminAccess;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ApiDocsController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.api-docs.index', [
            'canOpenDashboard' => AdminAccess::can($request->user(), 'dashboard'),
        ]);
    }

    /**
     * The OpenAPI document. It lives outside public/ so only signed-in admins can read it.
     */
    public function spec(): Response
    {
        return response(file_get_contents(resource_path('api-docs/openapi.yaml')), 200, [
            'Content-Type' => 'application/yaml; charset=utf-8',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
