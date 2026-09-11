<?php

namespace App\Enums;

use App\Models\Employee;
use Illuminate\Support\Str;

enum CourseDocumentType: string
{
    case FundamentacionVigilancia = 'fundamentacion_vigilancia';
    case ReentrenamientoVigilancia = 'reentrenamiento_vigilancia';
    case EspecializacionSeguridadComercial = 'especializacion_seguridad_comercial';
    case EspecializacionEntidadesOficiales = 'especializacion_entidades_oficiales';
    case EspecializacionGrandesSuperficies = 'especializacion_grandes_superficies';
    case EspecializacionSeguridadResidencial = 'especializacion_seguridad_residencial';
    case EspecializacionSeguridadAeroportuaria = 'especializacion_seguridad_aeroportuaria';
    case EspecializacionSectorFinanciero = 'especializacion_sector_financiero';
    case EspecializacionSectorPetrolero = 'especializacion_sector_petrolero';
    case EspecializacionSectorIndustrial = 'especializacion_sector_industrial';
    case EspecializacionSectorHospitalario = 'especializacion_sector_hospitalario';
    case FundamentacionEscolta = 'fundamentacion_escolta';
    case ReentrenamientoEscolta = 'reentrenamiento_escolta';
    case EspecializacionEscoltaPersonas = 'especializacion_escolta_personas';
    case EspecializacionManejoDefensivo = 'especializacion_manejo_defensivo';
    case FundamentacionSupervisor = 'fundamentacion_supervisor';
    case ReentrenamientoSupervisor = 'reentrenamiento_supervisor';
    case FundamentacionOmt = 'fundamentacion_omt';
    case ReentrenamientoOmt = 'reentrenamiento_omt';
    case EspecializacionCoordinador = 'especializacion_coordinador';
    case EspecializacionInstalador = 'especializacion_instalador';
    case SeminarioDirectivos = 'seminario_directivos';
    case SeminarioCoordinadores = 'seminario_coordinadores';
    case SeminarioJefesRh = 'seminario_jefes_rh';
    case ManejoArmasFuego = 'manejo_armas_fuego';
    case Otro = 'curso_otro';

    public function label(): string
    {
        return match ($this) {
            self::FundamentacionVigilancia => 'Curso de fundamentación en vigilancia',
            self::ReentrenamientoVigilancia => 'Curso de reentrenamiento en vigilancia',
            self::EspecializacionSeguridadComercial => 'Especialización en seguridad comercial',
            self::EspecializacionEntidadesOficiales => 'Especialización en entidades oficiales',
            self::EspecializacionGrandesSuperficies => 'Especialización en grandes superficies',
            self::EspecializacionSeguridadResidencial => 'Especialización en seguridad residencial',
            self::EspecializacionSeguridadAeroportuaria => 'Especialización en seguridad aeroportuaria',
            self::EspecializacionSectorFinanciero => 'Especialización en sector financiero',
            self::EspecializacionSectorPetrolero => 'Especialización en sector petrolero',
            self::EspecializacionSectorIndustrial => 'Especialización en sector industrial',
            self::EspecializacionSectorHospitalario => 'Especialización en sector hospitalario',
            self::FundamentacionEscolta => 'Curso de fundamentación en escolta',
            self::ReentrenamientoEscolta => 'Curso de reentrenamiento en escolta',
            self::EspecializacionEscoltaPersonas => 'Especialización en escolta a personas',
            self::EspecializacionManejoDefensivo => 'Especialización en manejo defensivo',
            self::FundamentacionSupervisor => 'Curso de fundamentación para supervisor',
            self::ReentrenamientoSupervisor => 'Curso de reentrenamiento para supervisor',
            self::FundamentacionOmt => 'Curso de fundamentación en operador de medios tecnológicos',
            self::ReentrenamientoOmt => 'Curso de reentrenamiento en operador de medios tecnológicos',
            self::EspecializacionCoordinador => 'Especialización para coordinador',
            self::EspecializacionInstalador => 'Especialización para instalador',
            self::SeminarioDirectivos => 'Seminario para directivos de seguridad privada',
            self::SeminarioCoordinadores => 'Seminario para coordinadores de seguridad',
            self::SeminarioJefesRh => 'Seminario para jefes de recursos humanos en seguridad privada',
            self::ManejoArmasFuego => 'Curso de manejo y uso de armas de fuego',
            self::Otro => 'Otro curso o capacitación',
        };
    }

    public function requirement(): DocumentRequirement
    {
        return match ($this) {
            self::Otro => DocumentRequirement::Optional,
            default => DocumentRequirement::IfApplies,
        };
    }

    public function isRepeatable(): bool
    {
        return $this === self::Otro;
    }

    public function isCatalog(): bool
    {
        return $this !== self::Otro;
    }

    public function filenameSlug(): string
    {
        return Str::of($this->label())
            ->ascii()
            ->replaceMatches('/[^A-Za-z0-9]+/', '_')
            ->trim('_')
            ->limit(60, '')
            ->toString();
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
