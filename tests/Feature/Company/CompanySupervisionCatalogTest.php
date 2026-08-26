<?php

declare(strict_types=1);

namespace Tests\Feature\Company;

use App\Models\SupervisorChecklistItem;
use App\Models\SupervisorShiftTemplate;
use App\Models\SupervisorZone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CompanySupervisionCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_admin_can_manage_supervision_catalogs(): void
    {
        $this->seedWithPilot();
        $admin = User::query()->where('email', 'empresa@sj-seguridad.test')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('company.supervision-zones.index'))
            ->assertOk()
            ->assertSee('Norte')
            ->assertSee('Zonas');

        $this->actingAs($admin)->post(route('company.supervision-zones.store'), [
            'name' => 'Occidente',
            'is_active' => '1',
        ])->assertRedirect(route('company.supervision-zones.index'));

        $this->assertDatabaseHas('supervisor_zones', [
            'name' => 'Occidente',
            'security_company_id' => $admin->security_company_id,
        ]);

        $this->actingAs($admin)
            ->get(route('company.supervision-shifts.index'))
            ->assertOk()
            ->assertSee('Día')
            ->assertSee('Noche');

        $this->actingAs($admin)->post(route('company.supervision-shifts.store'), [
            'name' => 'Tarde',
            'starts_at' => '14:00',
            'ends_at' => '22:00',
            'is_active' => '1',
        ])->assertRedirect(route('company.supervision-shifts.index'));

        $this->assertDatabaseHas('supervisor_shift_templates', [
            'name' => 'Tarde',
            'starts_at' => '14:00',
            'ends_at' => '22:00',
        ]);

        $this->actingAs($admin)
            ->get(route('company.supervision-preop.index'))
            ->assertOk()
            ->assertSee('Casco')
            ->assertSee('Luces');

        $this->actingAs($admin)->post(route('company.supervision-preop.store'), [
            'kind' => 'ppe',
            'name' => 'Gafas de seguridad',
        ])->assertRedirect(route('company.supervision-preop.index'));

        $this->assertDatabaseHas('supervisor_checklist_items', [
            'name' => 'Gafas de seguridad',
            'kind' => 'ppe',
        ]);
    }

    public function test_guard_cannot_access_supervision_catalogs(): void
    {
        $this->seedWithPilot();
        $guard = User::query()->where('email', 'guardia@control-acceso.test')->firstOrFail();

        $this->actingAs($guard)->get(route('company.supervision-zones.index'))->assertForbidden();
        $this->actingAs($guard)->get(route('company.supervision-shifts.index'))->assertForbidden();
        $this->actingAs($guard)->get(route('company.supervision-preop.index'))->assertForbidden();
    }

    public function test_company_admin_can_deactivate_zone_and_template(): void
    {
        $this->seedWithPilot();
        $admin = User::query()->where('email', 'empresa@sj-seguridad.test')->firstOrFail();
        $zone = SupervisorZone::query()
            ->where('security_company_id', $admin->security_company_id)
            ->where('name', 'Norte')
            ->firstOrFail();
        $template = SupervisorShiftTemplate::query()
            ->where('security_company_id', $admin->security_company_id)
            ->where('name', 'Día')
            ->firstOrFail();
        $item = SupervisorChecklistItem::query()
            ->where('security_company_id', $admin->security_company_id)
            ->where('name', 'Casco')
            ->firstOrFail();

        $this->actingAs($admin)->put(route('company.supervision-zones.update', $zone), [
            'name' => 'Norte',
            'is_active' => '0',
        ])->assertRedirect(route('company.supervision-zones.index'));

        $this->actingAs($admin)->put(route('company.supervision-shifts.update', $template), [
            'name' => 'Día',
            'starts_at' => '06:00',
            'ends_at' => '18:00',
            'is_active' => '0',
        ])->assertRedirect(route('company.supervision-shifts.index'));

        $this->actingAs($admin)->put(route('company.supervision-preop.update', $item), [
            'name' => 'Casco',
            'is_active' => '0',
        ])->assertRedirect(route('company.supervision-preop.index'));

        $this->assertFalse($zone->fresh()->is_active);
        $this->assertFalse($template->fresh()->is_active);
        $this->assertFalse($item->fresh()->is_active);
    }
}
