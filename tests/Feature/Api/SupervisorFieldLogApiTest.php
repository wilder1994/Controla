<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\SupervisorRecommendationStatus;
use App\Models\Client;
use App\Models\SupervisorAlarmType;
use App\Models\SupervisorControlBookType;
use App\Models\SupervisorDocumentType;
use App\Models\SupervisorRecommendation;
use App\Models\SupervisorRiskType;
use App\Models\SupervisorSupportType;
use App\Models\SupervisorWeaponBrand;
use App\Models\SupervisorWeaponType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SupervisorFieldLogApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_exposes_aligned_modules(): void
    {
        $token = $this->loginSupervisor();

        $response = $this->withToken($token)->getJson('/api/supervision/catalog');
        $response->assertOk();

        $keys = collect($response->json('modules'))->pluck('key')->all();
        $this->assertSame(
            ['reviews', 'inventory', 'control_books', 'folders', 'weapons', 'recommendations', 'alarms', 'supports', 'documents'],
            $keys,
        );
        $this->assertFalse(collect($response->json('modules'))->firstWhere('key', 'supports')['requires_client']);
        $this->assertFalse(collect($response->json('modules'))->firstWhere('key', 'inventory')['requires_client']);
        $this->assertTrue(collect($response->json('modules'))->firstWhere('key', 'inventory')['hangs_off_review']);
        $this->assertTrue(collect($response->json('modules'))->firstWhere('key', 'control_books')['hangs_off_review']);
        $this->assertFalse(collect($response->json('modules'))->firstWhere('key', 'documents')['hangs_off_review']);
        $this->assertFalse(collect($response->json('modules'))->firstWhere('key', 'documents')['requires_client']);
        $this->assertSame(
            'repeatable',
            collect($response->json('modules'))->firstWhere('key', 'inventory')['fields'][0]['type'],
        );
        $this->assertSame(
            'repeatable',
            collect($response->json('modules'))->firstWhere('key', 'documents')['fields'][0]['type'],
        );
        $this->assertTrue(collect($response->json('modules'))->firstWhere('key', 'alarms')['requires_client']);
        $this->assertFalse(collect($response->json('modules'))->firstWhere('key', 'alarms')['hangs_off_review']);
        $this->assertSame(
            'photo_grid',
            collect($response->json('modules'))->firstWhere('key', 'weapons')
                ? collect(collect($response->json('modules'))->firstWhere('key', 'weapons')['fields'])->firstWhere('type', 'photo_grid')['type']
                : null,
        );
        $recs = collect($response->json('modules'))->firstWhere('key', 'recommendations');
        $this->assertSame('repeatable', $recs['fields'][0]['type']);
        $this->assertSame(3, $recs['fields'][0]['max']);
        $this->assertSame(
            'photo_grid',
            collect($recs['fields'][0]['item_fields'])->firstWhere('type', 'photo_grid')['type'],
        );
        $this->assertSame(
            'risk_type_id',
            collect($recs['fields'][0]['item_fields'])->firstWhere('name', 'risk_type_id')['name'],
        );
        $weapons = collect($response->json('modules'))->firstWhere('key', 'weapons');
        $this->assertSame('novelty', collect($weapons['fields'])->firstWhere('name', 'novelty')['name']);
        $this->assertSame('cleaned', collect($weapons['fields'])->firstWhere('name', 'cleaned')['name']);
        $alarms = collect($response->json('modules'))->firstWhere('key', 'alarms');
        $this->assertSame('alarm_type_id', $alarms['fields'][0]['name']);
        $this->assertSame('kind', collect($alarms['fields'])->firstWhere('name', 'kind')['name']);
        $supports = collect($response->json('modules'))->firstWhere('key', 'supports');
        $this->assertSame('support_type_id', $supports['fields'][0]['name']);
    }

    public function test_catalog_documents_uses_company_types(): void
    {
        $token = $this->loginSupervisor();
        $type = $this->documentTypeForPilot();

        $options = collect($this->withToken($token)->getJson('/api/supervision/catalog')->json('modules'))
            ->firstWhere('key', 'documents')['fields'][0]['item_fields'][0]['options'];

        $this->assertContains(
            ['value' => (string) $type->id, 'label' => 'Carta de notificación'],
            $options,
        );
    }

    public function test_catalog_control_books_uses_company_types(): void
    {
        $token = $this->loginSupervisor();
        $type = $this->controlBookTypeForPilot();

        $options = collect($this->withToken($token)->getJson('/api/supervision/catalog')->json('modules'))
            ->firstWhere('key', 'control_books')['fields'][0]['item_fields'][0]['options'];

        $this->assertContains(
            ['value' => (string) $type->id, 'label' => 'Minuta'],
            $options,
        );
    }

    public function test_catalog_recommendations_uses_company_types(): void
    {
        $token = $this->loginSupervisor();
        $type = $this->riskTypeForPilot();

        $options = collect($this->withToken($token)->getJson('/api/supervision/catalog')->json('modules'))
            ->firstWhere('key', 'recommendations')['fields'][0]['item_fields'][0]['options'];

        $this->assertContains(
            ['value' => (string) $type->id, 'label' => 'Riesgo físico'],
            $options,
        );
    }

    public function test_catalog_alarms_and_supports_use_company_types(): void
    {
        $token = $this->loginSupervisor();
        $alarm = $this->alarmTypeForPilot();
        $support = $this->supportTypeForPilot();

        $modules = collect($this->withToken($token)->getJson('/api/supervision/catalog')->json('modules'));
        $alarmOptions = collect($modules->firstWhere('key', 'alarms')['fields'])->firstWhere('name', 'alarm_type_id')['options'];
        $supportOptions = collect($modules->firstWhere('key', 'supports')['fields'])->firstWhere('name', 'support_type_id')['options'];

        $this->assertContains(['value' => (string) $alarm->id, 'label' => 'Pánico'], $alarmOptions);
        $this->assertContains(['value' => (string) $support->id, 'label' => 'Refuerzo de puesto'], $supportOptions);
    }

    public function test_inventory_and_support_and_recommendation_record(): void
    {
        $token = $this->loginSupervisor();
        $client = Client::query()->where('slug', 'palmas-del-ingenio')->firstOrFail();

        $this->withToken($token)->post('/api/supervision/shifts/open', $this->supervisorShiftOpenPayload())->assertCreated();

        $review = $this->withToken($token)->post('/api/supervision/reviews', $this->supervisorReviewPayload($client));
        $review->assertCreated();
        $reviewId = (int) $review->json('review.id');
        $riskType = $this->riskTypeForPilot();

        $this->withToken($token)->postJson('/api/supervision/logs', [
            'module' => 'inventory',
            'supervisor_shift_review_id' => $reviewId,
            'payload' => [
                'items' => [
                    ['type' => 'Extintor', 'status' => 'bad', 'notes' => 'Manómetro en rojo'],
                ],
            ],
        ])->assertCreated()->assertJsonPath('log.outcome', 'critical');

        $this->withToken($token)->postJson('/api/supervision/logs', [
            'module' => 'supports',
            'payload' => [
                'support_type_id' => $this->supportTypeForPilot()->id,
                'reason' => 'Apoyo en vía por novedad de alarma',
            ],
        ])->assertCreated();

        $opened = $this->withToken($token)->postJson('/api/supervision/logs', [
            'module' => 'recommendations',
            'supervisor_shift_review_id' => $reviewId,
            'payload' => [
                'items' => [
                    $this->recommendationItemPayload($riskType, [
                        'risk' => 'Tramos oscuros en el costado norte.',
                        'likelihood' => '5',
                        'impact' => '4',
                    ]),
                    $this->recommendationItemPayload($riskType, [
                        'risk' => 'La chapa queda suelta al cerrar.',
                        'likelihood' => '2',
                        'impact' => '2',
                    ]),
                ],
            ],
        ]);
        $opened->assertCreated()->assertJsonPath('log.outcome', 'critical');
        $recId = $opened->json('log.supervisor_recommendation_id');
        $this->assertNotNull($recId);
        $this->assertSame('recorded', SupervisorRecommendation::query()->find($recId)?->status->value);
        $this->assertSame('extreme', SupervisorRecommendation::query()->find($recId)?->risk_level->value);
        $this->assertSame(2, SupervisorRecommendation::query()->where('opened_shift_id', $opened->json('log.supervisor_shift_id'))->count());

        $this->withToken($token)->patchJson('/api/supervision/recommendations/'.$recId, [
            'status' => SupervisorRecommendationStatus::Progress->value,
        ])->assertNotFound();
    }

    public function test_field_log_requires_supervision_flag(): void
    {
        $token = $this->loginSupervisor();
        $client = Client::query()->where('slug', 'torres-loma')->firstOrFail();
        $this->assertFalse((bool) $client->has_supervision);

        $this->withToken($token)->post('/api/supervision/shifts/open', $this->supervisorShiftOpenPayload());

        $this->withToken($token)->postJson('/api/supervision/logs', [
            'module' => 'alarms',
            'client_id' => $client->id,
            'payload' => [
                'alarm_type_id' => 1,
                'kind' => 'test',
                'result' => 'ok',
            ],
        ])->assertUnprocessable();
    }

    public function test_all_log_modules_accept_catalog_payloads(): void
    {
        $token = $this->loginSupervisor();
        $client = Client::query()->where('slug', 'palmas-del-ingenio')->firstOrFail();
        $this->withToken($token)->post('/api/supervision/shifts/open', $this->supervisorShiftOpenPayload());

        $reviewId = (int) $this->withToken($token)
            ->post('/api/supervision/reviews', $this->supervisorReviewPayload($client))
            ->assertCreated()
            ->json('review.id');

        $docType = $this->documentTypeForPilot();
        $bookType = $this->controlBookTypeForPilot();
        $riskType = $this->riskTypeForPilot();
        [$weaponType, $weaponBrand] = $this->weaponCatalogForPilot();
        $alarmType = $this->alarmTypeForPilot();
        $supportType = $this->supportTypeForPilot();

        $payloads = [
            'inventory' => [
                'items' => [
                    ['type' => 'Radio', 'status' => 'good', 'notes' => null],
                ],
            ],
            'control_books' => [
                'items' => [
                    [
                        'control_book_type_id' => $bookType->id,
                        'novelty' => 'no',
                        'notes' => null,
                    ],
                ],
            ],
            'documents' => [
                'items' => [
                    [
                        'document_type_id' => $docType->id,
                        'delivered' => 5,
                        'pending' => 5,
                        'notes' => null,
                    ],
                ],
            ],
            'folders' => ['status' => 'complete'],
            'weapons' => $this->weaponPayload($weaponType, $weaponBrand),
            'alarms' => ['alarm_type_id' => $alarmType->id, 'kind' => 'test', 'result' => 'ok'],
            'supports' => ['support_type_id' => $supportType->id, 'reason' => 'Apoyo a puesto vecino por novedad'],
            'recommendations' => [
                'items' => [$this->recommendationItemPayload($riskType)],
            ],
        ];

        foreach ($payloads as $module => $payload) {
            $body = ['module' => $module, 'payload' => $payload];
            if (in_array($module, ['inventory', 'control_books', 'folders', 'weapons', 'recommendations'], true)) {
                $body['supervisor_shift_review_id'] = $reviewId;
            } elseif ($module === 'alarms') {
                $body['client_id'] = $client->id;
            }
            $this->withToken($token)->postJson('/api/supervision/logs', $body)->assertCreated();
        }
    }

    public function test_hanging_module_requires_review(): void
    {
        $token = $this->loginSupervisor();
        $client = Client::query()->where('slug', 'palmas-del-ingenio')->firstOrFail();
        $this->withToken($token)->post('/api/supervision/shifts/open', $this->supervisorShiftOpenPayload());

        $this->withToken($token)->postJson('/api/supervision/logs', [
            'module' => 'inventory',
            'client_id' => $client->id,
            'payload' => [
                'items' => [
                    ['type' => 'Extintor', 'status' => 'good'],
                ],
            ],
        ])->assertUnprocessable();
    }

    private function loginSupervisor(): string
    {
        $this->seedWithPilot();

        $login = $this->postJson('/api/supervision/login', [
            'email' => 'supervisor@sj-seguridad.test',
            'password' => 'Super123!',
        ]);
        $login->assertOk();

        return (string) $login->json('token');
    }

    private function documentTypeForPilot(): SupervisorDocumentType
    {
        $companyId = (int) User::query()->where('email', 'supervisor@sj-seguridad.test')->value('security_company_id');

        return SupervisorDocumentType::query()->create([
            'security_company_id' => $companyId,
            'name' => 'Carta de notificación',
            'is_active' => true,
            'sort_order' => 10,
        ]);
    }

    private function controlBookTypeForPilot(): SupervisorControlBookType
    {
        $companyId = (int) User::query()->where('email', 'supervisor@sj-seguridad.test')->value('security_company_id');

        return SupervisorControlBookType::query()->create([
            'security_company_id' => $companyId,
            'name' => 'Minuta',
            'is_active' => true,
            'sort_order' => 10,
        ]);
    }

    private function riskTypeForPilot(string $name = 'Riesgo físico'): SupervisorRiskType
    {
        $companyId = (int) User::query()->where('email', 'supervisor@sj-seguridad.test')->value('security_company_id');

        return SupervisorRiskType::query()->create([
            'security_company_id' => $companyId,
            'name' => $name,
            'is_active' => true,
            'sort_order' => 10,
        ]);
    }

    private function alarmTypeForPilot(string $name = 'Pánico'): SupervisorAlarmType
    {
        $companyId = (int) User::query()->where('email', 'supervisor@sj-seguridad.test')->value('security_company_id');

        return SupervisorAlarmType::query()->create([
            'security_company_id' => $companyId,
            'name' => $name,
            'is_active' => true,
            'sort_order' => 10,
        ]);
    }

    private function supportTypeForPilot(string $name = 'Refuerzo de puesto'): SupervisorSupportType
    {
        $companyId = (int) User::query()->where('email', 'supervisor@sj-seguridad.test')->value('security_company_id');

        return SupervisorSupportType::query()->create([
            'security_company_id' => $companyId,
            'name' => $name,
            'is_active' => true,
            'sort_order' => 10,
        ]);
    }

    /** @return array{0: SupervisorWeaponType, 1: SupervisorWeaponBrand} */
    private function weaponCatalogForPilot(): array
    {
        $companyId = (int) User::query()->where('email', 'supervisor@sj-seguridad.test')->value('security_company_id');

        return [
            SupervisorWeaponType::query()->create([
                'security_company_id' => $companyId,
                'name' => 'Pistola',
                'is_active' => true,
                'sort_order' => 10,
            ]),
            SupervisorWeaponBrand::query()->create([
                'security_company_id' => $companyId,
                'name' => 'Glock',
                'is_active' => true,
                'sort_order' => 10,
            ]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function weaponPayload(SupervisorWeaponType $type, SupervisorWeaponBrand $brand): array
    {
        return [
            'weapon_type_id' => $type->id,
            'weapon_brand_id' => $brand->id,
            'serial' => 'AR-9981',
            'caliber' => '9 mm',
            'permit_kind' => 'tenencia',
            'permit_number' => 'PT-123',
            'permit_expires_at' => now()->addYear()->toDateString(),
            'ammo_quantity' => 12,
            'ammo_caliber' => '9 mm',
            'novelty' => 'no',
            'cleaned' => 'no',
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function recommendationItemPayload(SupervisorRiskType $type, array $overrides = []): array
    {
        return array_merge([
            'risk_type_id' => $type->id,
            'risk' => 'La chapa queda suelta al cerrar.',
            'likelihood' => '3',
            'impact' => '3',
            'consequence' => 'Ingreso no autorizado al conjunto.',
            'treatment' => 'Cambiar chapa y reforzar marco.',
        ], $overrides);
    }
}
