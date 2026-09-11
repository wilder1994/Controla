<?php

namespace App\Enums;

use App\Models\Employee;
use Illuminate\Support\Str;

enum CertificateDocumentType: string
{
    case ExamenMedicoIngreso = 'examen_medico_ingreso';
    case ExamenPsicofisico = 'examen_psicofisico';
    case ExamenPsicosensometrico = 'examen_psicosensometrico';

    public function label(): string
    {
        return match ($this) {
            self::ExamenMedicoIngreso => 'Examen médico ocupacional de ingreso',
            self::ExamenPsicofisico => 'Examen psicofísico',
            self::ExamenPsicosensometrico => 'Examen psicosensométrico',
        };
    }

    public function requirement(): DocumentRequirement
    {
        return match ($this) {
            self::ExamenMedicoIngreso => DocumentRequirement::Required,
            self::ExamenPsicofisico,
            self::ExamenPsicosensometrico => DocumentRequirement::IfApplies,
        };
    }

    public function filenameSlug(): string
    {
        return match ($this) {
            self::ExamenMedicoIngreso => 'Examen_medico_ingreso',
            self::ExamenPsicofisico => 'Examen_psicofisico',
            self::ExamenPsicosensometrico => 'Examen_psicosensometrico',
        };
    }

    public function suggestedName(Employee $employee): string
    {
        $who = Str::of($employee->full_name)
            ->ascii()
            ->replaceMatches('/[^A-Za-z0-9]+/', '_')
            ->trim('_')
            ->toString();

        return $this->filenameSlug().'_'.$employee->document_number.'_'.$who;
    }
}
