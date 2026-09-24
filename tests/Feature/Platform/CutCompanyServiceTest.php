<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Enums\ArchiveReason;
use App\Enums\ClientLifecycle;
use App\Enums\SubscriptionStatus;
use App\Models\Client;
use App\Models\User;
use App\Support\Company\CompanyServiceAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CutCompanyServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_cut_and_company_admin_browses_read_only(): void
    {
        $this->seedWithPilot();

        $platform = User::query()->where('email', 'admin@control-acceso.test')->firstOrFail();
        $company = $this->pilotCompany();
        $admin = User::query()->where('email', 'empresa@sj-seguridad.test')->firstOrFail();

        $this->actingAs($platform)
            ->get(route('admin.companies.show', $company))
            ->assertOk()
            ->assertSee('Acceso al sistema')
            ->assertSee('Suspender acceso')
            ->assertSee('Se apaga el sistema ahora');

        $this->actingAs($platform)
            ->from(route('admin.companies.show', $company))
            ->post(route('admin.companies.cut', $company))
            ->assertRedirect(route('admin.companies.show', $company));

        $company->refresh();
        $this->assertFalse($company->is_active);
        $this->assertSame(SubscriptionStatus::Suspended, $company->subscription_status);
        $this->assertNotNull($company->suspended_at);

        $this->actingAs($admin)
            ->get(route('company.dashboard'))
            ->assertOk()
            ->assertSee('Servicio suspendido');

        $this->actingAs($admin)
            ->post(route('company.job-titles.store'), ['name' => 'No debe crearse'])
            ->assertForbidden();

        $this->assertDatabaseMissing('company_job_titles', [
            'security_company_id' => $company->id,
            'name' => 'No debe crearse',
        ]);
    }

    public function test_non_admin_cannot_login_when_service_is_cut(): void
    {
        $this->seedWithPilot();

        $platform = User::query()->where('email', 'admin@control-acceso.test')->firstOrFail();
        $company = $this->pilotCompany();
        $this->actingAs($platform)->post(route('admin.companies.cut', $company));

        $guard = User::query()->where('email', 'guardia@control-acceso.test')->firstOrFail();

        $this->post('/logout');

        $this->from('/login')
            ->post('/login', [
                'email' => $guard->email,
                'password' => 'Guardia123!',
            ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => CompanyServiceAccess::DENIED]);

        $this->assertGuest();
    }

    public function test_supervisor_api_login_is_blocked_when_service_is_cut(): void
    {
        $this->seedWithPilot();

        $platform = User::query()->where('email', 'admin@control-acceso.test')->firstOrFail();
        $this->actingAs($platform)->post(route('admin.companies.cut', $this->pilotCompany()));

        $user = $this->companySupervisor();

        $this->postJson('/api/supervision/login', [
            'login' => $user->username,
            'password' => self::COMPANY_SUPERVISOR_PASSWORD,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['login']);
    }

    public function test_reactivate_restores_access_and_unarchives_clients(): void
    {
        $this->seedWithPilot();

        $platform = User::query()->where('email', 'admin@control-acceso.test')->firstOrFail();
        $company = $this->pilotCompany();
        $admin = User::query()->where('email', 'empresa@sj-seguridad.test')->firstOrFail();

        $this->actingAs($platform)
            ->post(route('admin.companies.archive', $company), [
                'archive_reason' => ArchiveReason::NonPayment->value,
            ])
            ->assertRedirect(route('admin.companies.show', $company));

        $this->assertNotNull($company->fresh()->archived_at);
        $this->assertTrue(
            Client::query()
                ->where('security_company_id', $company->id)
                ->where('lifecycle', ClientLifecycle::ArchivedCompany)
                ->exists()
        );

        $this->actingAs($platform)
            ->post(route('admin.companies.reactivate-service', $company))
            ->assertRedirect(route('admin.companies.show', $company));

        $company->refresh();
        $this->assertTrue($company->is_active);
        $this->assertNull($company->archived_at);
        $this->assertSame(SubscriptionStatus::Active, $company->subscription_status);

        $this->actingAs($admin)
            ->get(route('company.dashboard'))
            ->assertOk()
            ->assertDontSee('Servicio suspendido');
    }
}
