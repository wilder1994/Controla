<?php

namespace App\Enums;

use App\Models\Employee;
use Illuminate\Support\Str;

enum ContractingDocumentType: string
{
    case ContratoTrabajo = 'contrato_trabajo';
    case ClausulaConfidencialidad = 'clausula_confidencialidad';
    case CodigoEtica = 'conocimiento_codigo_etica';
    case AcuerdoResponsabilidad = 'acuerdo_responsabilidad_laboral';
    case PerfilCargo = 'perfil_del_cargo';
    case InduccionCorporativa = 'induccion_reinduccion_corporativa';
    case RegistroFotoHuellas = 'registro_fotografico_huellas';
    case EntregaCarne = 'entrega_carne';
    case CartaPresentacion = 'carta_presentacion_empleado';

    public function label(): string
    {
        return match ($this) {
            self::ContratoTrabajo => 'Contrato de trabajo',
            self::ClausulaConfidencialidad => 'Cláusula de confidencialidad',
            self::CodigoEtica => 'Conocimiento código de ética y conducta',
            self::AcuerdoResponsabilidad => 'Acuerdo de responsabilidad laboral',
            self::PerfilCargo => 'Perfil del cargo',
            self::InduccionCorporativa => 'Certificado de inducción o reinducción corporativa',
            self::RegistroFotoHuellas => 'Registro fotográfico y de huellas dactilares',
            self::EntregaCarne => 'Entrega de carné',
            self::CartaPresentacion => 'Carta de presentación del empleado',
        };
    }

    public function requirement(): DocumentRequirement
    {
        return DocumentRequirement::Required;
    }

    public function filenameSlug(): string
    {
        return match ($this) {
            self::ContratoTrabajo => 'Contrato_trabajo',
            self::ClausulaConfidencialidad => 'Clausula_confidencialidad',
            self::CodigoEtica => 'Codigo_etica_conducta',
            self::AcuerdoResponsabilidad => 'Acuerdo_responsabilidad_laboral',
            self::PerfilCargo => 'Perfil_cargo',
            self::InduccionCorporativa => 'Induccion_reinduccion_corporativa',
            self::RegistroFotoHuellas => 'Registro_fotografico_huellas',
            self::EntregaCarne => 'Entrega_carne',
            self::CartaPresentacion => 'Carta_presentacion_empleado',
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
