<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\StructureMember;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

final class MembersAssemblyExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(
        private readonly int $clientId,
    ) {}

    public function query()
    {
        return StructureMember::query()
            ->with('structure')
            ->where('client_id', $this->clientId)
            ->orderBy('last_name')
            ->orderBy('first_name');
    }

    /** @param StructureMember $row */
    public function map($row): array
    {
        return [
            $row->last_name.' '.$row->first_name,
            $row->document_number,
            $row->member_type->label(),
            $row->structure?->full_path ?? '—',
            $row->phone_primary ?? '—',
            $row->email ?? '—',
            $row->has_app_access ? 'Sí' : 'No',
        ];
    }

    public function headings(): array
    {
        return [
            'Nombre completo',
            'Documento',
            'Tipo',
            'Unidad',
            'Teléfono',
            'Email',
            'Acceso APP',
        ];
    }
}
