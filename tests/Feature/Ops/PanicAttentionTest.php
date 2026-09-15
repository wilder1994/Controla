<?php

declare(strict_types=1);

namespace Tests\Feature\Ops;

use App\Enums\PanicAttentionStatus;
use App\Models\OperationalAlert;
use App\Models\PanicAttention;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PanicAttentionTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendee_creates_open_sheet_and_can_print(): void
    {
        $this->seedWithPilot();
        $actor = User::query()->where('email', 'empresa@sj-seguridad.test')->firstOrFail();
        $other = User::factory()->create([
            'security_company_id' => $actor->security_company_id,
            'name' => 'Atiende pánico',
            'username' => 'atiende.panico.9902',
            'is_active' => true,
        ]);
        $other->assignRole('company-admin');
        $this->grantCompanyAdminCatalog($other);

        $this->actingAs($actor)
            ->postJson(route('company.ops.panic'), ['note' => 'Ayuda', 'latitude' => 3.45, 'longitude' => -76.53])
            ->assertCreated();

        $alertId = (int) OperationalAlert::query()->where('actor_user_id', $actor->id)->value('id');

        $poll = $this->actingAs($other)->getJson(route('company.ops.alerts'));
        $poll->assertOk()->assertJsonPath('alerts.0.can_attend', true);

        $this->actingAs($other)
            ->postJson(route('company.panics.claim'), ['alert_id' => $alertId])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $attention = PanicAttention::query()->where('operational_alert_id', $alertId)->firstOrFail();
        $this->assertSame(PanicAttentionStatus::Abierto, $attention->status);
        $this->assertTrue($attention->isAttendedBy($other));

        $this->actingAs($other)
            ->get(route('company.panics.show', $attention))
            ->assertOk()
            ->assertSee($attention->folio())
            ->assertSee('Observaciones');

        $this->actingAs($other)
            ->get(route('company.panics.print', $attention))
            ->assertOk()
            ->assertSee('Imprimir / Guardar PDF')
            ->assertSee($attention->folio());

        $this->actingAs($actor)
            ->postJson(route('company.panics.claim'), ['alert_id' => $alertId])
            ->assertForbidden();
    }

    public function test_only_attendee_closes_sheet(): void
    {
        $this->seedWithPilot();
        $actor = User::query()->where('email', 'empresa@sj-seguridad.test')->firstOrFail();
        $attendee = User::factory()->create([
            'security_company_id' => $actor->security_company_id,
            'username' => 'cierra.panico.9903',
            'is_active' => true,
        ]);
        $attendee->assignRole('company-admin');
        $this->grantCompanyAdminCatalog($attendee);
        $intruder = User::factory()->create([
            'security_company_id' => $actor->security_company_id,
            'username' => 'otro.panico.9904',
            'is_active' => true,
        ]);
        $intruder->assignRole('company-admin');
        $this->grantCompanyAdminCatalog($intruder);

        $this->actingAs($actor)->postJson(route('company.ops.panic'), ['note' => 'Ya'])->assertCreated();
        $alertId = (int) OperationalAlert::query()->where('actor_user_id', $actor->id)->latest('id')->value('id');

        $this->actingAs($attendee)->postJson(route('company.panics.claim'), ['alert_id' => $alertId])->assertOk();
        $attention = PanicAttention::query()->where('operational_alert_id', $alertId)->firstOrFail();

        $this->actingAs($intruder)
            ->put(route('company.panics.update', $attention), [
                'observations' => 'No me toca',
                'close' => '1',
            ])
            ->assertForbidden();

        $this->actingAs($attendee)
            ->put(route('company.panics.update', $attention), [
                'observations' => 'Llegó patrulla y se normalizó.',
                'close' => '1',
            ])
            ->assertRedirect(route('company.panics.show', $attention));

        $this->assertSame(PanicAttentionStatus::Cerrado, $attention->fresh()->status);
        $this->assertSame('Llegó patrulla y se normalizó.', $attention->fresh()->observations);
    }

    public function test_second_claim_is_conflict(): void
    {
        $this->seedWithPilot();
        $actor = User::query()->where('email', 'empresa@sj-seguridad.test')->firstOrFail();
        $first = User::factory()->create([
            'security_company_id' => $actor->security_company_id,
            'username' => 'primero.panico.9905',
            'is_active' => true,
        ]);
        $first->assignRole('company-admin');
        $this->grantCompanyAdminCatalog($first);
        $second = User::factory()->create([
            'security_company_id' => $actor->security_company_id,
            'username' => 'segundo.panico.9906',
            'is_active' => true,
        ]);
        $second->assignRole('company-admin');
        $this->grantCompanyAdminCatalog($second);

        $this->actingAs($actor)->postJson(route('company.ops.panic'), [])->assertCreated();
        $alertId = (int) OperationalAlert::query()->where('actor_user_id', $actor->id)->latest('id')->value('id');

        $this->actingAs($first)->postJson(route('company.panics.claim'), ['alert_id' => $alertId])->assertOk();
        $this->actingAs($second)->postJson(route('company.panics.claim'), ['alert_id' => $alertId])->assertStatus(409);
    }
}
