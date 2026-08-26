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

        $open = $this->withToken($token)->postJson('/api/supervision/shifts/open', ['km_start' => 1000]);
        $open->assertCreated();

        $ping = $this->withToken($token)->postJson('/api/supervision/shifts/ping', [
            'latitude' => 3.4516,
            'longitude' => -76.5320,
        ]);
        $ping->assertOk();

        $close = $this->withToken($token)->postJson('/api/supervision/shifts/close', ['km_end' => 1012]);
        $close->assertOk();
        $this->assertSame('closed', $close->json('shift.status'));
        $this->assertSame(12, $close->json('shift.km_traveled'));
    }

    public function test_login_fails_without_pro_package(): void
    {
        $this->seedWithPilot();

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

        $this->withToken($token)->postJson('/api/supervision/shifts/open');

        $this->withToken($token)->postJson('/api/supervision/reviews', [
            'client_id' => $client->id,
            'notes' => 'Revista',
        ])->assertUnprocessable();
    }
}
