<?php

namespace App\Enums;

use App\Models\Employee;
use Illuminate\Support\Str;

enum LaborHistoryDocumentType: string
{
    case RequisicionPersonal = 'requisicion_personal';
    case RegistroConocimiento = 'registro_conocimiento';
    case HojaVida = 'hoja_vida';
    case FotocopiaCedula = 'fotocopia_cedula';
    case CertificadoEtnia = 'certificado_etnia';
    case FotoTresPorCuatro = 'foto_3x4';
    case CertificadosEstudio = 'certificados_estudio';
    case ResolucionRetiroFuerza = 'resolucion_retiro_fuerza';
    case CertificacionesLaborales = 'certificaciones_laborales';
    case LibretaMilitar = 'libreta_militar';
    case VerificacionAntecedentes = 'verificacion_antecedentes';
    case LicenciaConduccion = 'licencia_conduccion';
    case TarjetaPropiedad = 'tarjeta_propiedad';
    case Soat = 'soat';
    case RevisionTecnomecanica = 'revision_tecnomecanica';
    case EvaluacionConocimiento = 'evaluacion_conocimiento';
    case EntrevistaSeleccion = 'entrevista_seleccion';
    case PruebaPsicotecnica = 'prueba_psicotecnica';
    case EntrevistaTecnica = 'entrevista_tecnica';
    case EstudioConfiabilidad = 'estudio_confiabilidad';
    case PruebaPoligrafia = 'prueba_poligrafia';
    case CertificadoEps = 'certificado_eps';
    case CertificadoAfp = 'certificado_afp';
    case CertificadoCesantias = 'certificado_cesantias';
    case DocumentosBeneficiarios = 'documentos_beneficiarios';
    case CertificacionBancaria = 'certificacion_bancaria';

    public function label(): string
    {
        return match ($this) {
            self::RequisicionPersonal => 'Formato de requisición de personal',
            self::RegistroConocimiento => 'Registro conocimiento del empleado',
            self::HojaVida => 'Hoja de vida',
            self::FotocopiaCedula => 'Fotocopia de la cédula de ciudadanía',
            self::CertificadoEtnia => 'Certificado etnia – factor de vulnerabilidad',
            self::FotoTresPorCuatro => 'Foto 3×4 fondo blanco o azul',
            self::CertificadosEstudio => 'Certificados de estudio formal y no formal',
            self::ResolucionRetiroFuerza => 'Resolución de retiro de entidades de fuerza pública',
            self::CertificacionesLaborales => 'Certificaciones laborales con verificación de referencias',
            self::LibretaMilitar => 'Libreta militar',
            self::VerificacionAntecedentes => 'Verificación de antecedentes',
            self::LicenciaConduccion => 'Licencia de conducción (categoría A2 / B1 según aplique)',
            self::TarjetaPropiedad => 'Tarjeta de propiedad (licencia de tránsito)',
            self::Soat => 'SOAT (Seguro Obligatorio de Accidentes de Tránsito)',
            self::RevisionTecnomecanica => 'Revisión tecno mecánica',
            self::EvaluacionConocimiento => 'Evaluación de conocimiento',
            self::EntrevistaSeleccion => 'Entrevista selección',
            self::PruebaPsicotecnica => 'Prueba psicotécnica',
            self::EntrevistaTecnica => 'Entrevista técnica de líder de área',
            self::EstudioConfiabilidad => 'Estudio de confiabilidad',
            self::PruebaPoligrafia => 'Prueba de poligrafía',
            self::CertificadoEps => 'Certificado de EPS',
            self::CertificadoAfp => 'Certificado de AFP',
            self::CertificadoCesantias => 'Certificado de cesantías',
            self::DocumentosBeneficiarios => 'Documentos de beneficiarios',
            self::CertificacionBancaria => 'Certificación bancaria',
        };
    }

    public function requirement(): DocumentRequirement
    {
        return match ($this) {
            self::LibretaMilitar => DocumentRequirement::Optional,
            self::CertificadoEtnia,
            self::ResolucionRetiroFuerza,
            self::EvaluacionConocimiento,
            self::EstudioConfiabilidad,
            self::PruebaPoligrafia => DocumentRequirement::IfApplies,
            default => DocumentRequirement::Required,
        };
    }

    public function filenameSlug(): string
    {
        return match ($this) {
            self::RequisicionPersonal => 'Requisicion_personal',
            self::RegistroConocimiento => 'Registro_conocimiento',
            self::HojaVida => 'Hoja_de_vida',
            self::FotocopiaCedula => 'Fotocopia_cedula',
            self::CertificadoEtnia => 'Certificado_etnia',
            self::FotoTresPorCuatro => 'Foto_3x4',
            self::CertificadosEstudio => 'Certificados_estudio',
            self::ResolucionRetiroFuerza => 'Resolucion_retiro_fuerza',
            self::CertificacionesLaborales => 'Certificaciones_laborales',
            self::LibretaMilitar => 'Libreta_militar',
            self::VerificacionAntecedentes => 'Verificacion_antecedentes',
            self::LicenciaConduccion => 'Licencia_conduccion',
            self::TarjetaPropiedad => 'Tarjeta_propiedad',
            self::Soat => 'SOAT',
            self::RevisionTecnomecanica => 'Revision_tecno_mecanica',
            self::EvaluacionConocimiento => 'Evaluacion_conocimiento',
            self::EntrevistaSeleccion => 'Entrevista_seleccion',
            self::PruebaPsicotecnica => 'Prueba_psicotecnica',
            self::EntrevistaTecnica => 'Entrevista_tecnica',
            self::EstudioConfiabilidad => 'Estudio_confiabilidad',
            self::PruebaPoligrafia => 'Prueba_poligrafia',
            self::CertificadoEps => 'Certificado_EPS',
            self::CertificadoAfp => 'Certificado_AFP',
            self::CertificadoCesantias => 'Certificado_cesantias',
            self::DocumentosBeneficiarios => 'Documentos_beneficiarios',
            self::CertificacionBancaria => 'Certificacion_bancaria',
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
