<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Página expirada</title>
    <style>
        body { font-family: Figtree, system-ui, sans-serif; background: #020617; color: #e2e8f0; margin: 0; min-height: 100vh; display: grid; place-items: center; }
        .box { max-width: 28rem; padding: 1.5rem; text-align: center; }
        h1 { font-size: 1.125rem; margin: 0 0 .5rem; }
        p { font-size: .875rem; color: #94a3b8; line-height: 1.5; }
        a { color: #818cf8; }
    </style>
</head>
<body>
    <div class="box">
        <h1>La página expiró</h1>
        <p>La sesión o el formulario se venció. Recarga e intenta guardar de nuevo.</p>
        <p><a href="{{ url()->previous() ?: url('/company/settings') }}">Volver</a></p>
    </div>
</body>
</html>
