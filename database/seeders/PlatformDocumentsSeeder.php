<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\LegalCorpusType;
use App\Models\DocumentRetentionSeries;
use App\Models\LegalCorpusVersion;
use Illuminate\Database\Seeder;

final class PlatformDocumentsSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedLegalCorpus();
        $this->seedTrd();
    }

    private function seedLegalCorpus(): void
    {
        $items = [
            [LegalCorpusType::Contract, 'Contrato de licencia SaaS Controla. El suscriptor acepta el uso del software bajo el paquete contratado, ciclo de facturación y políticas de acceso descritas en la plataforma.'],
            [LegalCorpusType::Terms, 'Términos y condiciones de uso de la plataforma Controla. Incluye obligaciones de las partes, limitación de responsabilidad y jurisdicción aplicable en Colombia.'],
            [LegalCorpusType::PrivacyPolicy, 'Política de tratamiento de datos personales alineada a Ley 1581 de 2012. Define roles responsable/encargado, finalidades del tratamiento y derechos ARCO de titulares.'],
            [LegalCorpusType::ProcedureLifecycle, 'Procedimiento operativo: gracia 5 días → suspensión (bloqueo) → archivo por falta de pago tras plazo configurable → retención legal → purga operativa del censo tenant.'],
        ];

        foreach ($items as [$type, $body]) {
            $content = $body."\n\nVersión inicial para desarrollo y pruebas. Revisión legal pendiente antes del go-live comercial.";
            $hash = hash('sha256', $content);

            LegalCorpusVersion::query()->updateOrCreate(
                ['type' => $type->value, 'version' => '1.0'],
                [
                    'title' => $type->label(),
                    'content' => $content,
                    'effective_from' => now()->toDateString(),
                    'superseded_at' => null,
                    'content_hash' => $hash,
                ],
            );
        }
    }

    private function seedTrd(): void
    {
        $rows = [
            ['Comercial', 'Contrato + aceptación', '10 años', null, 'Conservar', 'CCom art. 60 / Ley 962'],
            ['Comercial', 'Factura electrónica', '10 años', 3650, 'Conservar', 'Sistema FE DIAN'],
            ['Comercial', 'Actas de ciclo', '10 años', 3650, 'Conservar', 'TRD interna'],
            ['Operativo tenant', 'Censo / visitantes / logs', '365 días post baja', 365, 'Purga certificada', 'Ley 1581'],
            ['Normativa interna', 'Políticas publicadas', 'Histórico vigente', null, 'Conservar', 'Ley 594 metodología'],
        ];

        foreach ($rows as $i => [$series, $subseries, $label, $days, $disposition, $basis]) {
            DocumentRetentionSeries::query()->updateOrCreate(
                ['series' => $series, 'subseries' => $subseries],
                [
                    'retention_label' => $label,
                    'retention_days' => $days,
                    'disposition' => $disposition,
                    'legal_basis' => $basis,
                    'sort_order' => $i + 1,
                ],
            );
        }
    }
}
