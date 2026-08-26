<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\SupervisionPackageSku;
use App\Models\Client;
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
}
