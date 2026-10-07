<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>API Docs — MAQAM</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.17.14/swagger-ui.css">
    <style>
        body { margin: 0; background: #F7F6F2; font-family: Inter, ui-sans-serif, system-ui, sans-serif; }
        .docs-bar { position: sticky; top: 0; z-index: 10; display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: .75rem 1.5rem; background: #161b29; color: #fff; }
        .docs-bar strong { color: #dcb479; letter-spacing: .05em; }
        .docs-bar span { font-size: .8rem; opacity: .6; margin-inline-start: .5rem; }
        .docs-bar nav { display: flex; align-items: center; gap: .75rem; }
        .docs-bar a, .docs-bar button { font: inherit; font-size: .8rem; color: #fff; background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.12); border-radius: .5rem; padding: .4rem .8rem; text-decoration: none; cursor: pointer; }
        .docs-bar a:hover, .docs-bar button:hover { background: rgba(255,255,255,.16); }
        #swagger-ui { max-width: 1280px; margin: 0 auto; padding-bottom: 4rem; }
        .swagger-ui .topbar { display: none; }
    </style>
</head>
<body>
    <header class="docs-bar">
        <div><strong>MAQAM</strong><span>Mobile API v1 · private documentation</span></div>
        <nav>
            @if($canOpenDashboard)
                <a href="{{ route('admin.dashboard') }}">{{ __('admin.nav.command_center') }}</a>
            @endif
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit">{{ __('admin.logout') }}</button>
            </form>
        </nav>
    </header>

    <div id="swagger-ui"></div>

    <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.17.14/swagger-ui-bundle.js"></script>
    <script>
        SwaggerUIBundle({
            url: @js(route('admin.api-docs.spec')),
            dom_id: '#swagger-ui',
            deepLinking: true,
            docExpansion: 'list',
            defaultModelsExpandDepth: 0,
            defaultModelExpandDepth: 3,
            displayRequestDuration: true,
            filter: true,
            persistAuthorization: true,
            tryItOutEnabled: true,
        });
    </script>
</body>
</html>
