<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\SupervisorRecommendationStatus;
use App\Models\Client;
use App\Models\SupervisorRecommendation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SupervisorFieldLogApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_exposes_eight_aligned_modules(): void
    {
        $token = $this->loginSupervisor();

        $response = $this->withToken($token)->getJson('/api/supervision/catalog');
        $response->assertOk();

        $keys = collect($response->json('modules'))->pluck('key')->all();
        $this->assertSame(
            ['reviews', 'inventory', 'documents', 'folders', 'weapons', 'recommendations', 'alarms', 'supports'],
            $keys,
        );
        $this->assertFalse(collect($response->json('modules'))->firstWhere('key', 'supports')['requires_client']);
        $this->assertTrue(collect($response->json('modules'))->firstWhere('key', 'inventory')['requires_client']);
    }

    public function test_inventory_and_support_and_recommendation_lifecycle(): void
    {
        $token = $this->loginSupervisor();
        $client = Client::query()->where('slug', 'palmas-del-ingenio')->firstOrFail();

        $this->withToken($token)->post('/api/supervision/shifts/open', $this->supervisorShiftOpenPayload())->assertCreated();

        $this->withToken($token)->postJson('/api/supervision/logs', [
            'module' => 'inventory',
            'client_id' => $client->id,
            'payload' => ['condition' => 'novelty'],
        ])->assertCreated()->assertJsonPath('log.outcome', 'attention');

        $this->withToken($token)->postJson('/api/supervision/logs', [
            'module' => 'supports',
            'payload' => ['reason' => 'Apoyo en vía por novedad de alarma'],
        ])->assertCreated();

        $opened = $this->withToken($token)->postJson('/api/supervision/logs', [
            'module' => 'recommendations',
            'client_id' => $client->id,
            'payload' => [
                'title' => 'Reforzar iluminación del acceso peatonal',
                'body' => 'Quedan tramos oscuros en el costado norte.',
                'priority' => 'high',
            ],
        ]);
        $opened->assertCreated();
        $recId = $opened->json('log.supervisor_recommendation_id');
        $this->assertNotNull($recId);
        $this->assertSame('open', SupervisorRecommendation::query()->find($recId)?->status->value);

        $this->withToken($token)->patchJson('/api/supervision/recommendations/'.$recId, [
            'status' => SupervisorRecommendationStatus::Progress->value,
        ])->assertOk()->assertJsonPath('recommendation.status', 'progress');

        $this->withToken($token)->patchJson('/api/supervision/recommendations/'.$recId, [
            'status' => SupervisorRecommendationStatus::Closed->value,
        ])->assertOk()->assertJsonPath('recommendation.status', 'closed');
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
            'payload' => ['result' => 'ok'],
        ])->assertUnprocessable();
    }

    public function test_all_log_modules_accept_catalog_payloads(): void
    {
        $token = $this->loginSupervisor();
        $client = Client::query()->where('slug', 'palmas-del-ingenio')->firstOrFail();
        $this->withToken($token)->post('/api/supervision/shifts/open', $this->supervisorShiftOpenPayload());

        $payloads = [
            'inventory' => ['condition' => 'good'],
            'documents' => ['kind' => 'minuta', 'status' => 'delivered', 'quantity' => 1],
            'folders' => ['status' => 'complete'],
            'weapons' => ['serial' => 'AR-9981', 'ammo_ok' => true, 'novelty' => false],
            'alarms' => ['result' => 'ok'],
            'supports' => ['reason' => 'Apoyo a puesto vecino por novedad'],
            'recommendations' => [
                'title' => 'Revisar cerradura peatonal',
                'body' => 'La chapa queda suelta al cerrar.',
                'priority' => 'normal',
            ],
        ];

        foreach ($payloads as $module => $payload) {
            $body = ['module' => $module, 'payload' => $payload];
            if ($module !== 'supports') {
                $body['client_id'] = $client->id;
            }
            $this->withToken($token)->postJson('/api/supervision/logs', $body)->assertCreated();
        }
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
}
