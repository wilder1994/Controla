<?php

namespace Tests;

use App\Enums\CompanyPackageSku;
use App\Enums\SupervisorChecklistKind;
use App\Models\Client;
use App\Models\Employee;
use App\Models\SupervisorPost;
use App\Models\SupervisorChecklistItem;
use App\Models\SupervisorShiftTemplate;
use App\Models\SupervisorZone;
use App\Models\User;
use App\Services\Company\SeedSupervisorIntakeDefaultsService;
use App\Support\Legal\CorpusAcceptanceRules;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\PilotDemoSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\UploadedFile;

abstract class TestCase extends BaseTestCase
{
    /** Seed mínimo + datos piloto (empresa, conjuntos, censo, usuarios demo). */
    protected function seedWithPilot(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(PilotDemoSeeder::class);
    }

    /** @return array<string, array<string, string>> */
    protected function acceptAllCorpusDocs(?CompanyPackageSku $sku = null): array
    {
        $docs = [];
        foreach (CorpusAcceptanceRules::requiredTypeValues($sku) as $type) {
            $docs[$type] = '1';
        }

        return ['accept_docs' => $docs];
    }

    /** @return array<string, mixed> */
    protected function supervisorShiftOpenPayload(array $overrides = []): array
    {
        $companyId = (int) User::query()
            ->where('email', 'supervisor@sj-seguridad.test')
            ->value('security_company_id');

        if ($companyId > 0) {
            app(SeedSupervisorIntakeDefaultsService::class)->execute($companyId);
        }

        $zone = SupervisorZone::query()
            ->where('security_company_id', $companyId)
            ->orderBy('sort_order')
            ->first();
        $template = SupervisorShiftTemplate::query()
            ->where('security_company_id', $companyId)
            ->orderBy('sort_order')
            ->first();

        $ppe = [];
        foreach (array_keys(SupervisorChecklistItem::keyedLabels($companyId, SupervisorChecklistKind::Ppe)) as $key) {
            $ppe[$key] = true;
        }
        $vehicleCheck = [];
        foreach (array_keys(SupervisorChecklistItem::keyedLabels($companyId, SupervisorChecklistKind::Vehicle)) as $key) {
            $vehicleCheck[$key] = true;
        }

        return array_merge([
            'shift_template_id' => $template?->id,
            'zone_id' => $zone?->id,
            'km_start' => 1000,
            'vehicle' => [
                'plate' => 'ABC12D',
                'brand' => 'Yamaha',
                'line' => 'FZ',
                'model' => '2022',
            ],
            'ppe_checklist' => $ppe,
            'vehicle_checklist' => $vehicleCheck,
            'odometer_photo' => UploadedFile::fake()->image('odometer.jpg'),
            'selfie_photo' => UploadedFile::fake()->image('selfie.jpg'),
        ], $overrides);
    }

    /** @return array<string, mixed> */
    protected function supervisorShiftClosePayload(array $overrides = []): array
    {
        return array_merge([
            'km_end' => 1012,
            'odometer_photo' => UploadedFile::fake()->image('odometer-end.jpg'),
            'selfie_photo' => UploadedFile::fake()->image('selfie-end.jpg'),
        ], $overrides);
    }

    protected function supervisorVigilante(): Employee
    {
        return Employee::query()->where('document_number', '1144001122')->firstOrFail();
    }

    protected function supervisionPostFor(Client $client): SupervisorPost
    {
        $post = SupervisorPost::query()
            ->withoutGlobalScopes()
            ->where('client_id', $client->id)
            ->where('is_active', true)
            ->orderBy('id')
            ->first();

        $this->assertNotNull($post, 'El cliente piloto debe tener puestos de Supervisión.');

        return $post;
    }

    /** @return array<string, mixed> */
    protected function supervisorReviewPayload(Client $client, array $overrides = []): array
    {
        return array_merge([
            'client_id' => $client->id,
            'supervisor_post_id' => $this->supervisionPostFor($client)->id,
            'employee_id' => $this->supervisorVigilante()->id,
            'notes' => 'Revista de puesto',
            'has_novelty' => 0,
            'latitude' => 3.4516,
            'longitude' => -76.5320,
            'guard_photo' => UploadedFile::fake()->image('guard.jpg'),
        ], $overrides);
    }
}
