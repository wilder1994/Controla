<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Client;
use App\Models\SupervisorDocumentType;
use App\Models\SupervisorFieldLog;
use App\Models\SupervisorShiftLocation;
use App\Models\SupervisorShiftReview;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SupervisorOfflineSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_offline_pack_includes_sites_posts_and_guards(): void
    {
        $token = $this->loginSupervisor();
        $this->withToken($token)->post('/api/supervision/shifts/open', $this->supervisorShiftOpenPayload());

        $response = $this->withToken($token)->getJson('/api/supervision/offline-pack');
        $response->assertOk();
        $this->assertNotEmpty($response->json('sites'));
        $this->assertNotEmpty($response->json('posts'));
        $this->assertNotEmpty($response->json('guards'));
        $this->assertNotEmpty($response->json('modules'));
        $this->assertTrue(collect($response->json('posts'))->contains(
            fn (array $post) => isset($post['client_id'], $post['id'], $post['name']),
        ));
    }

    public function test_review_and_ping_replay_same_client_event_id(): void
    {
        $token = $this->loginSupervisor();
        $this->withToken($token)->post('/api/supervision/shifts/open', $this->supervisorShiftOpenPayload())->assertCreated();
        $client = Client::query()->where('slug', 'palmas-del-ingenio')->firstOrFail();
        $eventId = '11111111-1111-4111-8111-111111111111';

        $first = $this->withToken($token)->post('/api/supervision/reviews', $this->supervisorReviewPayload($client, [
            'client_event_id' => $eventId,
        ]));
        $first->assertCreated();
        $reviewId = (int) $first->json('review.id');

        $second = $this->withToken($token)->post('/api/supervision/reviews', $this->supervisorReviewPayload($client, [
            'client_event_id' => $eventId,
        ]));
        $second->assertSuccessful();
        $this->assertSame($reviewId, (int) $second->json('review.id'));
        $this->assertSame(1, SupervisorShiftReview::query()->where('client_event_id', $eventId)->count());

        $pingId = '22222222-2222-4222-8222-222222222222';
        $this->withToken($token)->postJson('/api/supervision/shifts/ping', [
            'latitude' => 3.4516,
            'longitude' => -76.5320,
            'client_event_id' => $pingId,
        ])->assertOk();
        $this->withToken($token)->postJson('/api/supervision/shifts/ping', [
            'latitude' => 3.4516,
            'longitude' => -76.5320,
            'client_event_id' => $pingId,
        ])->assertOk();
        $this->assertSame(1, SupervisorShiftLocation::query()->where('client_event_id', $pingId)->count());
    }

    public function test_field_log_and_close_replay_client_event_id(): void
    {
        $token = $this->loginSupervisor();
        $this->withToken($token)->post('/api/supervision/shifts/open', $this->supervisorShiftOpenPayload())->assertCreated();
        $docType = $this->documentTypeForPilot();
        $logId = '33333333-3333-4333-8333-333333333333';
        $payload = [
            'module' => 'documents',
            'client_event_id' => $logId,
            'payload' => [
                'items' => [
                    [
                        'document_type_id' => $docType->id,
                        'delivered' => 1,
                        'pending' => 0,
                    ],
                ],
            ],
        ];

        $this->withToken($token)->postJson('/api/supervision/logs', $payload)->assertCreated();
        $this->withToken($token)->postJson('/api/supervision/logs', $payload)->assertSuccessful();
        $this->assertSame(1, SupervisorFieldLog::query()->where('client_event_id', $logId)->count());

        $closeId = '44444444-4444-4444-8444-444444444444';
        $close = $this->supervisorShiftClosePayload(['client_event_id' => $closeId]);
        $this->withToken($token)->post('/api/supervision/shifts/close', $close)->assertOk();
        $this->withToken($token)->post('/api/supervision/shifts/close', $close)->assertOk();
    }

    private function loginSupervisor(): string
    {
        $this->seedWithPilot();

        return $this->loginCompanySupervisor();
    }

    private function documentTypeForPilot(): SupervisorDocumentType
    {
        $companyId = $this->pilotCompanyId();

        return SupervisorDocumentType::query()->create([
            'security_company_id' => $companyId,
            'name' => 'Minuta offline',
            'is_active' => true,
            'sort_order' => 10,
        ]);
    }
}
