<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $sheet->folio }} — Ficha de campo</title>
    <style>
        @page { size: letter; margin: 14mm 12mm; }
        * { box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            color: #0f172a;
            margin: 0;
            padding: 20px 28px 36px;
            font-size: 12px;
            line-height: 1.45;
        }
        .header {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            border-bottom: 3px solid #4f46e5;
            padding-bottom: 14px;
            margin-bottom: 16px;
        }
        .brand h1 { font-size: 18px; margin: 0 0 2px; color: #312e81; }
        .brand .sub { font-size: 11px; color: #475569; }
        .folio {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }
        .folio strong { display: block; font-size: 15px; color: #4f46e5; }
        .meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px 18px;
            margin-bottom: 16px;
            font-size: 11px;
        }
        .meta span { color: #64748b; }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 700;
            background: #e0e7ff;
            color: #3730a3;
        }
        .badge.nov { background: #fee2e2; color: #991b1b; }
        .guard { display: flex; gap: 14px; margin: 12px 0 16px; align-items: flex-start; }
        .guard img {
            width: 92px;
            height: 92px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
        }
        section {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 12px;
            margin-bottom: 10px;
            break-inside: avoid;
        }
        section h2 {
            margin: 0 0 8px;
            font-size: 12px;
            color: #312e81;
            border-bottom: 1px solid #e0e7ff;
            padding-bottom: 4px;
        }
        section p { margin: 0 0 4px; }
        .photos { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px; }
        .photos figure { margin: 0; width: 110px; }
        .photos img {
            width: 110px;
            height: 110px;
            object-fit: cover;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
        }
        .photos figcaption { font-size: 9px; color: #64748b; text-align: center; margin-top: 2px; }
        .footer {
            position: running(sheet-footer);
            margin-top: 22px;
            padding-top: 10px;
            border-top: 1px solid #cbd5e1;
            font-size: 10px;
            color: #64748b;
            display: flex;
            justify-content: space-between;
            gap: 12px;
        }
        .print-btn {
            position: fixed;
            top: 14px;
            right: 14px;
            background: #4f46e5;
            color: #fff;
            border: 0;
            padding: 10px 16px;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
        }
        @media print {
            .print-btn { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <button class="print-btn" type="button" onclick="window.print()">Imprimir / Guardar PDF</button>

    <div class="header">
        <div class="brand">
            <h1>Controla</h1>
            <div class="sub">{{ $sheet->companyName }}</div>
            <div class="sub">Ficha de campo · {{ $sheet->kind->label() }}</div>
        </div>
        <div class="folio">
            <strong>{{ $sheet->folio }}</strong>
            <div>{{ $sheet->recordedAt->format('d/m/Y H:i') }}</div>
            <span class="badge {{ $sheet->hasNovelty ? 'nov' : '' }}">
                {{ $sheet->hasNovelty ? 'Con novedad' : 'Sin novedad' }}
            </span>
        </div>
    </div>

    <div class="meta">
        <div><span>Supervisor</span><br>{{ $sheet->supervisorName }}@if($sheet->username) ({{ $sheet->username }})@endif</div>
        <div><span>Zona / turno</span><br>{{ $sheet->zoneName ?? '—' }}@if($sheet->shiftLabel) · {{ $sheet->shiftLabel }}@endif</div>
        @if($sheet->clientName)
            <div><span>Cliente</span><br>{{ $sheet->clientName }}</div>
        @endif
        @if($sheet->postName)
            <div><span>Puesto</span><br>{{ $sheet->postName }}</div>
        @endif
        @if($sheet->guardName)
            <div><span>Vigilante</span><br>{{ $sheet->guardName }}</div>
        @endif
        @if($sheet->latitude !== null && $sheet->longitude !== null)
            <div><span>GPS</span><br>{{ number_format($sheet->latitude, 6) }}, {{ number_format($sheet->longitude, 6) }}</div>
        @endif
    </div>

    @if($sheet->guardPhotoSrc || $sheet->notes)
        <div class="guard">
            @if($sheet->guardPhotoSrc)
                <img src="{{ $sheet->guardPhotoSrc }}" alt="Foto del vigilante">
            @endif
            @if($sheet->notes)
                <p><strong>Observaciones.</strong> {{ $sheet->notes }}</p>
            @endif
        </div>
    @endif

    @foreach($sheet->sections as $section)
        <section>
            <h2>{{ $section['title'] }}</h2>
            @foreach($section['rows'] as $row)
                <p>{{ $row }}</p>
            @endforeach
            @if(($section['photos'] ?? []) !== [])
                <div class="photos">
                    @foreach($section['photos'] as $photo)
                        <figure>
                            <img src="{{ $photo['src'] }}" alt="{{ $photo['label'] }}">
                            <figcaption>{{ $photo['label'] }}</figcaption>
                        </figure>
                    @endforeach
                </div>
            @endif
        </section>
    @endforeach

    <div class="footer">
        <span>{{ $sheet->folio }} · {{ $sheet->kind->label() }}</span>
        <span>{{ $sheet->supervisorName }} · {{ $sheet->recordedAt->format('d/m/Y H:i') }}</span>
        <span>Inmutable · generado desde Controla</span>
    </div>
</body>
</html>
