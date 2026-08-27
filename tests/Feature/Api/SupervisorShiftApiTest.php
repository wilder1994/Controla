<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\SupervisionPackageSku;
use App\Models\Client;
use App\Models\GuardLog;
use App\Models\User;
use App\Services\Tenant\AssignCompanySupervisionPackageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SupervisorShiftApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_supervisor_can_open_ping_and_close_shift(): void
    {
        $this->seedWithPilot();

        $user = User::query()->where('email', 'supervisor@sj-seguridad.test')->firstOrFail();
        app(AssignCompanySupervisionPackageService::class)->execute(
            $user->securityCompany,
            SupervisionPackageSku::Sit1,
        );

        $login = $this->postJson('/api/supervision/login', [
            'email' => 'supervisor@sj-seguridad.test',
            'password' => 'Super123!',
        ]);
        $login->assertOk();
        $token = $login->json('token');

        $open = $this->withToken($token)->post('/api/supervision/shifts/open', $this->supervisorShiftOpenPayload());
        $open->assertCreated();

        $ping = $this->withToken($token)->postJson('/api/supervision/shifts/ping', [
            'latitude' => 3.4516,
            'longitude' => -76.5320,
        ]);
        $ping->assertOk();

        $close = $this->withToken($token)->post('/api/supervision/shifts/close', $this->supervisorShiftClosePayload());
        $close->assertOk();
        $this->assertSame('closed', $close->json('shift.status'));
        $this->assertSame(12, $close->json('shift.km_traveled'));
    }

    public function test_open_shift_requires_ppe_and_photos(): void
    {
        $this->seedWithPilot();
        $login = $this->postJson('/api/supervision/login', [
            'email' => 'supervisor@sj-seguridad.test',
            'password' => 'Super123!',
        ]);
        $token = $login->json('token');

        $this->withToken($token)
            ->withHeaders(['Accept' => 'application/json'])
            ->post('/api/supervision/shifts/open', [
                'km_start' => 100,
            ])->assertUnprocessable();
    }

    public function test_intake_lists_checklists_and_empty_fleet(): void
    {
        $this->seedWithPilot();
        $login = $this->postJson('/api/supervision/login', [
            'email' => 'supervisor@sj-seguridad.test',
            'password' => 'Super123!',
        ]);
        $token = $login->json('token');

        $this->withToken($token)->getJson('/api/supervision/intake')
            ->assertOk()
            ->assertJsonPath('first_vehicle', true)
            ->assertJsonPath('zones.0.name', 'Norte')
            ->assertJsonPath('shift_templates.0.name', 'Día')
            ->assertJsonStructure(['ppe', 'vehicle_check', 'zones', 'shift_templates', 'vehicles']);
    }

    public function test_login_fails_without_pro_package(): void
    {
        $this->seedWithPilot();

        $user = User::query()->where('email', 'supervisor@sj-seguridad.test')->firstOrFail();
        app(AssignCompanySupervisionPackageService::class)->execute($user->securityCompany, null);

        $this->postJson('/api/supervision/login', [
            'email' => 'supervisor@sj-seguridad.test',
            'password' => 'Super123!',
        ])->assertForbidden();
    }

    public function test_pro_review_requires_supervision_flag(): void
    {
        $this->seedWithPilot();

        $user = User::query()->where('email', 'supervisor@sj-seguridad.test')->firstOrFail();
        app(AssignCompanySupervisionPackageService::class)->execute(
            $user->securityCompany,
            SupervisionPackageSku::Sit5,
        );

        $client = Client::query()->where('slug', 'palmas-del-ingenio')->firstOrFail();
        $client->update(['has_supervision' => false]);

        $login = $this->postJson('/api/supervision/login', [
            'email' => 'supervisor@sj-seguridad.test',
            'password' => 'Super123!',
        ]);
        $token = $login->json('token');

        $this->withToken($token)->post('/api/supervision/shifts/open', $this->supervisorShiftOpenPayload());

        $this->withToken($token)->postJson('/api/supervision/reviews', [
            'client_id' => $client->id,
            'notes' => 'Revista',
        ])->assertUnprocessable();
    }

    public function test_review_saves_supervisor_gps_without_minuta(): void
    {
        $this->seedWithPilot();
        $login = $this->postJson('/api/supervision/login', [
            'email' => 'supervisor@sj-seguridad.test',
            'password' => 'Super123!',
        ]);
        $token = $login->json('token');
        $this->withToken($token)->post('/api/supervision/shifts/open', $this->supervisorShiftOpenPayload());

        $client = Client::query()->where('slug', 'palmas-del-ingenio')->firstOrFail();
        $guardLogsBefore = GuardLog::query()->count();

        $posts = $this->withToken($token)->getJson('/api/supervision/posts?client_id='.$client->id);
        $posts->assertOk();
        $this->assertNotEmpty($posts->json('posts'));
        $postNames = collect($posts->json('posts'))->pluck('name')->all();
        $this->assertContains('Portería principal', $postNames);
        $this->assertNotContains('Puerta principal', $postNames);

        $guards = $this->withToken($token)->getJson('/api/supervision/guards?document=1144001122');
        $guards->assertOk()->assertJsonPath('guards.0.document_number', '1144001122');

        $saved = $this->withToken($token)->post('/api/supervision/reviews', $this->supervisorReviewPayload($client, [
            'has_novelty' => 1,
            'latitude' => 3.4481,
            'longitude' => -76.5312,
        ]));
        $saved->assertCreated()
            ->assertJsonPath('review.client_id', $client->id)
            ->assertJsonPath('review.has_novelty', true)
            ->assertJsonPath('review.supervisor_post_id', $this->supervisionPostFor($client)->id);
        $this->assertEqualsWithDelta(3.4481, (float) $saved->json('review.latitude'), 0.0002);
        $this->assertEqualsWithDelta(-76.5312, (float) $saved->json('review.longitude'), 0.0002);

        $this->assertSame($guardLogsBefore, GuardLog::query()->count());

        $current = $this->withToken($token)->getJson('/api/supervision/shifts/current');
        $current->assertOk()->assertJsonPath('current_review.id', $saved->json('review.id'));
    }

    public function test_review_requires_photo_and_gps(): void
    {
        $this->seedWithPilot();
        $login = $this->postJson('/api/supervision/login', [
            'email' => 'supervisor@sj-seguridad.test',
            'password' => 'Super123!',
        ]);
        $token = $login->json('token');
        $this->withToken($token)->post('/api/supervision/shifts/open', $this->supervisorShiftOpenPayload());

        $client = Client::query()->where('slug', 'palmas-del-ingenio')->firstOrFail();
        $payload = $this->supervisorReviewPayload($client);
        unset($payload['guard_photo'], $payload['latitude'], $payload['longitude']);

        $this->withToken($token)
            ->withHeaders(['Accept' => 'application/json'])
            ->post('/api/supervision/reviews', $payload)
            ->assertUnprocessable();
    }
}
