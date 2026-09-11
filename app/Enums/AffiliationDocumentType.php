<?php

namespace App\Enums;

use App\Models\Employee;
use Illuminate\Support\Str;

enum AffiliationDocumentType: string
{
    case Estado = 'certificado_estado_afiliaciones';
    case Eps = 'certificado_eps';
    case Cesantias = 'certificado_cesantias';
    case Afp = 'certificado_afp';
    case Arl = 'afiliacion_arl';
    case Caja = 'certificado_caja';
    case SeguroVida = 'afiliacion_seguro_vida';
    case Exequial = 'afiliacion_exequial';

    public function label(): string
    {
        return match ($this) {
            self::Estado => 'Certificado estado de afiliaciones',
            self::Eps => 'Afiliación a EPS',
            self::Cesantias => 'Afiliación a cesantías',
            self::Afp => 'Afiliación a fondo de pensiones',
            self::Arl => 'Afiliación a ARL',
            self::Caja => 'Afiliación a caja de compensación familiar',
            self::SeguroVida => 'Afiliación seguro de vida',
            self::Exequial => 'Afiliación o carta de desistimiento de seguro exequial',
        };
    }

    public function requirement(): DocumentRequirement
    {
        return DocumentRequirement::Required;
    }

    public function filenameSlug(): string
    {
        return match ($this) {
            self::Estado => 'Estado_afiliaciones',
            self::Eps => 'Afiliacion_EPS',
            self::Cesantias => 'Afiliacion_cesantias',
            self::Afp => 'Afiliacion_pensiones',
            self::Arl => 'Afiliacion_ARL',
            self::Caja => 'Afiliacion_caja',
            self::SeguroVida => 'Afiliacion_seguro_vida',
            self::Exequial => 'Afiliacion_exequial',
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
