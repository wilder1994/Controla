<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>Reporte de Accesos — {{ config('app.name') }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            color: #0f172a;
            margin: 0;
            padding: 32px 40px;
            font-size: 12px;
            line-height: 1.5;
        }
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 16px;
            margin-bottom: 20px;
        }
        .header .brand { display: flex; align-items: center; gap: 12px; }
        .header img { height: 44px; }
        .header h1 { font-size: 18px; margin: 0 0 2px; }
        .header .sub { font-size: 11px; color: #475569; }
        .meta { font-size: 11px; color: #334155; }
        .meta strong { color: #0f172a; }
        .summary {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
        }
        .summary .card {
            flex: 1;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 10px 14px;
            text-align: center;
        }
        .summary .card .num { font-size: 22px; font-weight: 800; }
        .summary .card .lbl { font-size: 10px; text-transform: uppercase; color: #64748b; letter-spacing: .04em; }
        table { width: 100%; border-collapse: collapse; }
        th {
            text-align: left;
            background: #f1f5f9;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #475569;
            padding: 8px 10px;
            border-bottom: 1px solid #cbd5e1;
        }
        td {
            padding: 7px 10px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
        }
        tr:nth-child(even) td { background: #f8fafc; }
        .footer {
            margin-top: 28px;
            padding-top: 12px;
            border-top: 1px solid #cbd5e1;
            font-size: 10px;
            color: #64748b;
            display: flex;
            justify-content: space-between;
        }
        .print-btn {
            position: fixed;
            top: 16px;
            right: 16px;
            background: #2563eb;
            color: #fff;
            border: 0;
            padding: 10px 18px;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            font-size: 13px;
        }
        @media print {
            .print-btn { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print()">Imprimir / Guardar PDF</button>

    <div class="header">
        <div class="brand">
            @if($client && $client->logo_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($client->logo_path))
                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($client->logo_path) }}" alt="Logo">
            @endif
            <div>
                <h1>{{ $client?->name ?? 'Reporte de Accesos' }}</h1>
                <div class="sub">Reporte de control de accesos · Generado con {{ config('app.name') }}</div>
            </div>
        </div>
        <div class="meta">
            <div><strong>Generado:</strong> {{ now()->format('d/m/Y H:i') }}</div>
            <div><strong>Periodo:</strong> {{ request('date_from') ? request('date_from') : 'inicio' }} al {{ request('date_to') ? request('date_to') : 'hoy' }}</div>
            <div><strong>Rango de registros:</strong> {{ $logs->count() }}</div>
        </div>
    </div>

    <div class="summary">
        <div class="card"><div class="num">{{ $total }}</div><div class="lbl">Total</div></div>
        <div class="card"><div class="num">{{ $completed }}</div><div class="lbl">Completados</div></div>
        <div class="card"><div class="num">{{ $active }}</div><div class="lbl">Activos</div></div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Persona</th>
                <th>Documento</th>
                <th>Tipo</th>
                <th>Destino</th>
                <th>Vehículo</th>
                <th>Ingreso</th>
                <th>Salida</th>
            </tr>
        </thead>
        <tbody>
            @foreach($logs as $i => $log)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $log->visitor?->full_name ?? $log->resident?->full_name ?? $log->user?->name ?? '-' }}</td>
                    <td>{{ $log->visitor?->document_number ?? $log->resident?->document_number ?? '-' }}</td>
                    <td>{{ str_replace('_', ' ', $log->access_type) }}</td>
                    <td>{{ $log->housingUnit?->full_label ?? $log->location?->name ?? '-' }}</td>
                    <td>{{ $log->vehicle?->plate ?? '-' }}</td>
                    <td>{{ $log->entry_time?->format('d/m/Y H:i') ?? '-' }}</td>
                    <td>{{ $log->exit_time?->format('d/m/Y H:i') ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <span>Documento generado automáticamente por {{ config('app.name') }}.</span>
        <span>Página 1 de 1 · {{ now()->format('d/m/Y H:i') }}</span>
    </div>
</body>
</html>