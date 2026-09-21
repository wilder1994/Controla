<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { margin: 0; background: #0f172a; color: #e2e8f0; font: 12px/1.4 ui-sans-serif, system-ui, sans-serif; }
        .xlsx-preview-table { border-collapse: collapse; min-width: 100%; }
        .xlsx-preview-table td { border: 1px solid #1e293b; padding: 4px 8px; white-space: nowrap; max-width: 14rem; overflow: hidden; text-overflow: ellipsis; }
        .xlsx-preview-banner { margin: 0; padding: 10px 12px; background: #1e293b; color: #cbd5e1; font-size: 12px; border-bottom: 1px solid #334155; }
    </style>
</head>
<body>
    @if (! empty($fallback))
        <p class="xlsx-preview-banner">Vista aproximada (sin LibreOffice). En el servidor se muestra el PDF igual al Excel. Usa Descargar para el archivo original.</p>
    @endif
    {!! $table !!}
</body>
</html>
