<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $attention->folio() }} — Atención de pánico</title>
    <style>
        @page { size: letter; margin: 14mm 12mm; }
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', system-ui, sans-serif; color: #0f172a; margin: 0; padding: 20px 28px 36px; font-size: 12px; line-height: 1.45; }
        .header { display: flex; justify-content: space-between; gap: 16px; align-items: flex-start; border-bottom: 2px solid #0f172a; padding-bottom: 14px; margin-bottom: 14px; }
        .brand { display: flex; gap: 14px; align-items: flex-start; }
        .brand img { width: 72px; height: 72px; object-fit: contain; border: 1px solid #e2e8f0; border-radius: 6px; background: #fff; }
        .brand h1 { font-size: 16px; margin: 0 0 2px; }
        .brand .legal, .brand .nit { font-size: 11px; color: #64748b; }
        .folio { text-align: right; font-variant-numeric: tabular-nums; }
        .folio strong { display: block; font-size: 14px; }
        .doc-title { font-size: 14px; font-weight: 700; margin: 0 0 10px; }
        .site { margin-bottom: 14px; padding: 10px 12px; border: 1px solid #cbd5e1; background: #f8fafc; }
        .site p { margin: 0; }
        .site span { color: #64748b; display: inline-block; min-width: 92px; }
        .meta { display: grid; grid-template-columns: 1fr 1fr; gap: 6px 18px; margin-bottom: 16px; font-size: 11px; }
        .meta span { color: #64748b; }
        section { border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 12px; margin-bottom: 10px; }
        section h2 { margin: 0 0 8px; font-size: 12px; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; }
        .footer { margin-top: 22px; padding-top: 10px; border-top: 1px solid #cbd5e1; font-size: 10px; color: #64748b; display: flex; justify-content: space-between; gap: 12px; }
        .print-btn { position: fixed; top: 14px; right: 14px; background: #0f172a; color: #fff; border: 0; padding: 10px 16px; border-radius: 8px; font-weight: 700; cursor: pointer; }
        @media print { .print-btn { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
    <button class="print-btn" type="button" onclick="window.print()">Imprimir / Guardar PDF</button>
    @php $alert = $attention->alert; @endphp
    <div class="header">
        <div class="brand">
            @if($companyLogoSrc)
                <img src="{{ $companyLogoSrc }}" alt="Logo">
            @endif
            <div>
                <h1>{{ $company?->displayName() ?? 'Empresa' }}</h1>
                @if($company?->legal_name && $company->legal_name !== $company->displayName())
                    <div class="legal">{{ $company->legal_name }}</div>
                @endif
                @if($company?->tax_id)
                    <div class="nit">NIT {{ $company->tax_id }}</div>
                @endif
            </div>
        </div>
        <div class="folio">
            <strong>{{ $attention->folio() }}</strong>
            <div>{{ $alert?->created_at?->format('d/m/Y H:i') }}</div>
            <div>{{ $attention->status->label() }}</div>
        </div>
    </div>

    <p class="doc-title">Atención de pánico</p>

    <div class="site">
        <p><span>Cliente:</span> {{ $alert?->client?->name ?? '—' }}</p>
        <p><span>Instalación:</span> {{ $alert?->installation?->name ?? '—' }}</p>
    </div>

    <div class="meta">
        <div><span>Activó</span><br>{{ $alert?->actor?->name ?? '—' }}</div>
        <div><span>Atiende</span><br>{{ $attention->attendee?->name ?? '—' }}</div>
        <div><span>GPS</span><br>
            @if($alert?->latitude !== null && $alert?->longitude !== null)
                {{ number_format((float) $alert->latitude, 6) }}, {{ number_format((float) $alert->longitude, 6) }}
            @else
                Sin coordenadas
            @endif
        </div>
        <div><span>Cierre</span><br>{{ $attention->closed_at?->format('d/m/Y H:i') ?? 'Abierto' }}</div>
    </div>

    <section>
        <h2>Alerta</h2>
        <p>{{ $alert?->body }}</p>
    </section>
    <section>
        <h2>Observaciones de la atención</h2>
        <p>{{ $attention->observations ?: '—' }}</p>
    </section>

    <div class="footer">
        <span>{{ $attention->folio() }} · Atención de pánico</span>
        <span>{{ $attention->attendee?->name }} · {{ $attention->created_at?->format('d/m/Y H:i') }}</span>
        <span>{{ $company?->displayName() }}</span>
    </div>
</body>
</html>
