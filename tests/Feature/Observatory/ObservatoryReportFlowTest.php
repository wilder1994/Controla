<?php

declare(strict_types=1);

namespace Tests\Feature\Observatory;

use App\Enums\InstallationKind;
use App\Enums\ObservatoryEventStatus;
use App\Models\Client;
use App\Models\Installation;
use App\Models\ObservatoryEvent;
use App\Models\ObservatoryReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class ObservatoryReportFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_intake_lists_only_colegios_and_creates_event(): void
    {
        [$client, $colegio, $conjunto] = $this->sites();

        $this->get(route('observatory.public.show', $client->slug))
            ->assertOk()
            ->assertSee('Reportar', false)
            ->assertSee($client->name, false);

        $this->getJson(route('observatory.public.sites', ['slug' => $client->slug, 'q' => 'Santa']))
            ->assertOk()
            ->assertJsonPath('sites.0.id', $colegio->id)
            ->assertJsonMissing(['id' => $conjunto->id]);

        $this->getJson(route('observatory.public.sites', ['slug' => $client->slug, 'q' => 'Palmas']))
            ->assertOk()
            ->assertJsonPath('sites', []);

        $this->post(route('observatory.public.store', $client->slug), [
            'installation_id' => $conjunto->id,
            'kind' => 'hurto',
            'body' => 'Intentaron entrar por la portería del conjunto.',
            'is_anonymous' => '0',
            'reporter_name' => 'Ana Padre',
        ])->assertSessionHasErrors('installation_id');

        $this->post(route('observatory.public.store', $client->slug), [
            'installation_id' => $colegio->id,
            'kind' => 'hurto',
            'body' => 'Vieron a alguien saltando el muro del colegio.',
            'is_anonymous' => '0',
            'reporter_name' => 'Ana Padre',
            'reporter_phone' => '3001112233',
        ])->assertRedirect();

        $report = ObservatoryReport::query()->firstOrFail();
        $this->assertSame('comunidad', $report->source->value);
        $this->assertFalse($report->is_anonymous);
        $this->assertSame('Ana Padre', $report->reporter_name);
        $this->assertSame((int) $colegio->id, (int) $report->installation_id);

        $event = ObservatoryEvent::query()->firstOrFail();
        $this->assertSame(ObservatoryEventStatus::Nuevo, $event->status);
        $this->assertSame((int) $event->id, (int) $report->event_id);
        $this->assertSame(1, ObservatoryReport::query()->count());
    }

    public function test_anonymous_report_hides_identity_and_photo_is_optional(): void
    {
        Storage::fake('public');
        [$client, $colegio] = $this->sites();

        $this->post(route('observatory.public.store', $client->slug), [
            'installation_id' => $colegio->id,
            'kind' => 'amenaza',
            'body' => 'Amenazaron a un estudiante a la salida.',
            'is_anonymous' => '1',
            'reporter_name' => 'No debe guardarse',
            'reporter_phone' => '3000000000',
        ])->assertRedirect();

        $report = ObservatoryReport::query()->firstOrFail();
        $this->assertTrue($report->is_anonymous);
        $this->assertNull($report->reporter_name);
        $this->assertNull($report->reporter_phone);
        $this->assertNull($report->photo_path);

        $this->get(route('observatory.public.thanks', [$client->slug, $report]))
            ->assertOk()
            ->assertSee($report->event?->folio(), false);
    }

    public function test_optional_photo_is_stored(): void
    {
        Storage::fake('public');
        [$client, $colegio] = $this->sites();

        $this->post(route('observatory.public.store', $client->slug), [
            'installation_id' => $colegio->id,
            'kind' => 'rina',
            'body' => 'Hubo una riña en el descanso de la mañana.',
            'is_anonymous' => '1',
            'photo' => UploadedFile::fake()->image('patio.jpg'),
        ])->assertRedirect();

        $report = ObservatoryReport::query()->firstOrFail();
        $this->assertNotNull($report->photo_path);
        Storage::disk('public')->assertExists($report->photo_path);
    }

    public function test_company_and_client_admin_view_but_cannot_change_status(): void
    {
        [$client, $colegio] = $this->sites();
        $event = $this->openEvent($client, $colegio);
        $company = User::query()->where('email', 'empresa@sj-seguridad.test')->firstOrFail();
        $clientAdmin = User::query()->where('email', 'admin@palmasdelingenio.test')->firstOrFail();
        $session = ['tenancy.active_client_id' => $client->id];

        $this->actingAs($company)
            ->get(route('company.observatory.events.index'))
            ->assertOk()
            ->assertSee($event->folio(), false)
            ->assertSee('/o/'.$client->slug, false)
            ->assertSee('Copiar', false);

        $this->actingAs($company)
            ->get(route('company.observatory.events.show', $event))
            ->assertOk()
            ->assertSee('Los estados los cierra el admin de instalaciones', false);

        $this->actingAs($company)->withSession($session)
            ->patch(route('client.observatory.events.status', $event), ['status' => 'en_atencion'])
            ->assertForbidden();

        $this->actingAs($clientAdmin)->withSession($session)
            ->get(route('client.observatory.events.index'))
            ->assertOk()
            ->assertSee('/o/'.$client->slug, false)
            ->assertSee('Copiar', false);

        $this->actingAs($clientAdmin)->withSession($session)
            ->get(route('client.observatory.events.show', $event))
            ->assertOk()
            ->assertSee($event->folio(), false);

        $this->actingAs($clientAdmin)->withSession($session)
            ->patch(route('client.observatory.events.status', $event), ['status' => 'en_atencion'])
            ->assertForbidden();

        $this->assertSame(ObservatoryEventStatus::Nuevo, $event->fresh()->status);
    }

    public function test_installation_admin_closes_status_and_cannot_reopen(): void
    {
        [$client, $colegio, , $otherColegio] = $this->sites();
        $event = $this->openEvent($client, $colegio);
        $company = User::query()->where('email', 'empresa@sj-seguridad.test')->firstOrFail();
        $admin = $this->makeSiteAdmin($company, $client, $colegio, 'laura.obs@palmas.test', '1098000881');
        $other = $this->makeSiteAdmin($company, $client, $otherColegio, 'otro.obs@palmas.test', '1098000882');
        $support = $this->makeSiteAdmin($company, $client, $colegio, 'apoyo.obs@palmas.test', '1098000883', 'support');
        $session = ['tenancy.active_client_id' => $client->id];

        $this->actingAs($other)->withSession($session)
            ->patch(route('client.observatory.events.status', $event), ['status' => 'en_atencion'])
            ->assertForbidden();

        $this->actingAs($support)->withSession($session)
            ->patch(route('client.observatory.events.status', $event), ['status' => 'en_atencion'])
            ->assertForbidden();

        $this->actingAs($admin)->withSession($session)
            ->patch(route('client.observatory.events.status', $event), ['status' => 'cerrado'])
            ->assertSessionHasErrors('status');

        $this->actingAs($admin)->withSession($session)
            ->patch(route('client.observatory.events.status', $event), ['status' => 'en_atencion'])
            ->assertRedirect(route('client.observatory.events.show', $event));

        $this->assertSame(ObservatoryEventStatus::EnAtencion, $event->fresh()->status);

        $this->actingAs($admin)->withSession($session)
            ->patch(route('client.observatory.events.status', $event), ['status' => 'cerrado'])
            ->assertRedirect();

        $closed = $event->fresh();
        $this->assertSame(ObservatoryEventStatus::Cerrado, $closed->status);
        $this->assertNotNull($closed->closed_at);
        $this->assertSame((int) $admin->id, (int) $closed->closed_by_user_id);

        $this->actingAs($admin)->withSession($session)
            ->patch(route('client.observatory.events.status', $event), ['status' => 'en_atencion'])
            ->assertSessionHasErrors('status');
    }

    public function test_map_lists_only_scoped_colegios_with_coordinates(): void
    {
        config(['google-maps.api_key' => 'test-maps-key']);
        [$client, $colegio, $conjunto, $other] = $this->sites();
        $colegio->update(['latitude' => '3.4372200', 'longitude' => '-76.5225000']);
        $conjunto->update(['latitude' => '3.4516000', 'longitude' => '-76.5320000']);
        $other->update(['latitude' => '3.4600000', 'longitude' => '-76.5100000']);

        $company = User::query()->where('email', 'empresa@sj-seguridad.test')->firstOrFail();
        $this->actingAs($company)
            ->get(route('company.observatory.events.index'))
            ->assertOk()
            ->assertSee('test-maps-key', false)
            ->assertSee('IE Santa Librada', false)
            ->assertSee('IE Republica del Peru', false)
            ->assertSee('Pines', false)
            ->assertSee('Calor', false);

        $admin = $this->makeSiteAdmin($company, $client, $colegio, 'mapa.obs@palmas.test', '1098000884');
        $this->actingAs($admin)->withSession(['tenancy.active_client_id' => $client->id])
            ->get(route('client.observatory.events.index'))
            ->assertOk()
            ->assertSee('IE Santa Librada', false)
            ->assertDontSee('IE Republica del Peru', false);
    }

    public function test_guard_cannot_open_observatory(): void
    {
        $this->sites();
        $guard = User::query()->where('email', 'guardia@control-acceso.test')->firstOrFail();

        $this->actingAs($guard)
            ->get(route('company.observatory.events.index'))
            ->assertForbidden();

        $this->actingAs($guard)
            ->withSession(['tenancy.active_client_id' => Client::query()->where('slug', 'palmas-del-ingenio')->value('id')])
            ->get(route('client.observatory.events.index'))
            ->assertForbidden();
    }

    /** @return array{0: Client, 1: Installation, 2: Installation, 3: Installation} */
    private function sites(): array
    {
        $this->seedWithPilot();
        $client = Client::query()->where('slug', 'palmas-del-ingenio')->firstOrFail();
        $conjunto = Installation::query()->where('client_id', $client->id)->firstOrFail();
        $conjunto->update(['kind' => InstallationKind::Conjunto->value]);

        $colegio = Installation::query()->create([
            'client_id' => $client->id,
            'name' => 'IE Santa Librada',
            'kind' => InstallationKind::Colegio->value,
            'dane_code' => '176001000101',
            'is_client_site' => false,
            'is_active' => true,
            'city' => 'Cali',
        ]);

        $other = Installation::query()->create([
            'client_id' => $client->id,
            'name' => 'IE Republica del Peru',
            'kind' => InstallationKind::Colegio->value,
            'dane_code' => '176001000102',
            'is_client_site' => false,
            'is_active' => true,
            'city' => 'Cali',
        ]);

        return [$client, $colegio, $conjunto, $other];
    }

    private function openEvent(Client $client, Installation $colegio): ObservatoryEvent
    {
        $this->post(route('observatory.public.store', $client->slug), [
            'installation_id' => $colegio->id,
            'kind' => 'otro',
            'body' => 'Situación reportada para seguimiento del observatorio.',
            'is_anonymous' => '1',
        ])->assertRedirect();

        return ObservatoryEvent::query()->firstOrFail();
    }

    private function makeSiteAdmin(User $companyAdmin, Client $client, Installation $site, string $email, string $document, string $permission = 'admin'): User
    {
        $suffix = substr($document, -4);

        $this->actingAs($companyAdmin)->post(route('company.users.store'), [
            'role' => 'client-installation-admin',
            'origin' => 'external',
            'name' => 'Admin '.$suffix,
            'document_number' => $document,
            'job_title' => $permission === 'support' ? 'Auxiliar' : 'Rector',
            'email' => $email,
            'username' => ($permission === 'support' ? 'apoyo.obs.' : 'admin.obs.').$suffix,
            'password' => 'Cliente123!',
            'password_confirmation' => 'Cliente123!',
            'client_ids' => [$client->id],
            'installation_ids' => [$site->id],
            'site_permission' => $permission,
            'is_active' => '1',
        ])->assertRedirect();

        $user = User::query()->where('email', $email)->firstOrFail();
        $user->update(['must_change_password' => false]);

        return $user;
    }
}
