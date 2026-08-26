<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\AccessLog;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

final class AccessLogsExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(
        private readonly Request $request,
    ) {}

    public function query()
    {
        $query = AccessLog::with(['visitor', 'host', 'location', 'resident']);

        if ($this->request->filled('date_from')) {
            $query->whereDate('entry_time', '>=', $this->request->date_from);
        }
        if ($this->request->filled('date_to')) {
            $query->whereDate('entry_time', '<=', $this->request->date_to);
        }
        if ($this->request->filled('status')) {
            $query->where('status', $this->request->status);
        }
        if ($this->request->filled('location_id')) {
            $query->where('location_id', $this->request->location_id);
        }
        if ($this->request->filled('access_type')) {
            $query->where('access_type', $this->request->access_type);
        }

        return $query->latest('entry_time');
    }

    /** @param AccessLog $row */
    public function map($row): array
    {
        return [
            $row->visitor?->full_name ?? $row->resident?->full_name ?? '-',
            $row->visitor?->document_type && $row->visitor?->document_number
                ? $row->visitor->document_type.' '.$row->visitor->document_number
                : ($row->resident?->document_type && $row->resident?->document_number
                    ? $row->resident->document_type.' '.$row->resident->document_number
                    : '-'),
            $row->access_type,
            $row->host?->name ?? '-',
            $row->location?->name ?? '-',
            $row->entry_time->format('d/m/Y H:i'),
            $row->exit_time?->format('d/m/Y H:i') ?? '',
            $row->status === 'active' ? 'Dentro' : 'Salió',
            $row->purpose ?? '',
        ];
    }

    public function headings(): array
    {
        return [
            'Persona',
            'Documento',
            'Tipo',
            'Anfitrión',
            'Ubicación',
            'Ingreso',
            'Salida',
            'Estado',
            'Propósito',
        ];
    }
}
