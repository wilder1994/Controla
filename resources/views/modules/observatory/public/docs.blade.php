<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>API Observatorio — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5.17.14/swagger-ui.css">
    <style>
        body { margin: 0; background: #020617; color: #e2e8f0; font-family: Figtree, ui-sans-serif, system-ui; }
        .obs-docs-bar { padding: 1.25rem 1.5rem 0.75rem; border-bottom: 1px solid #1e293b; }
        .obs-docs-bar h1 { margin: 0; font-size: 1.25rem; font-weight: 700; }
        .obs-docs-bar p { margin: 0.35rem 0 0; color: #94a3b8; font-size: 0.875rem; max-width: 48rem; }
        .obs-docs-bar a { color: #5eead4; }
        #swagger-ui { max-width: 72rem; margin: 0 auto; padding: 0.5rem 1rem 2rem; }
        .swagger-ui { font-family: Figtree, ui-sans-serif, system-ui; }
        .swagger-ui .topbar { display: none; }
        .swagger-ui .info { margin: 16px 0; }
        .swagger-ui .info .title, .swagger-ui .info p, .swagger-ui .info li { color: #e2e8f0; }
        .swagger-ui .scheme-container { background: #0f172a; box-shadow: none; }
        .swagger-ui .opblock { background: #0f172a; border-color: #1e293b; }
        .swagger-ui .opblock .opblock-summary-description { color: #94a3b8; }
        .swagger-ui .opblock-tag { color: #e2e8f0; border-color: #1e293b; }
        .swagger-ui .model, .swagger-ui .model-title { color: #cbd5e1; }
        .swagger-ui .opblock-body pre.microlight { background: #020617 !important; }
    </style>
</head>
<body>
    <header class="obs-docs-bar">
        <h1>API del Observatorio</h1>
        <p>
            Contrato OpenAPI 3 para integrar eventos y riesgos de un cliente.
            Token: <code>POST /api/auth/login</code>.
            Especificación: <a href="{{ $specUrl }}">{{ $specUrl }}</a>
        </p>
    </header>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@5.17.14/swagger-ui-bundle.js"></script>
    <script>
        window.ui = SwaggerUIBundle({
            url: @json($specUrl),
            dom_id: '#swagger-ui',
            deepLinking: true,
            persistAuthorization: true,
            tryItOutEnabled: true,
        });
    </script>
</body>
</html>
