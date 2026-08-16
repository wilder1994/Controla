<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\StructureType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StructureTypeSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_manage_structure_types(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@control-acceso.test')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.settings.structure-types.index'))
            ->assertOk()
            ->assertSee('Tipos de estructura');

        $this->actingAs($admin)
            ->post(route('admin.settings.structure-types.store'), [
                'code' => 'clinic',
                'name' => 'Clínica',
                'description' => 'Local clínico',
                'is_unit' => true,
                'is_active' => true,
                'sort_order' => 120,
            ])
            ->assertRedirect(route('admin.settings.structure-types.index'));

        $this->assertDatabaseHas('structure_types', [
            'code' => 'clinic',
            'name' => 'Clínica',
            'is_unit' => 1,
        ]);

        $type = StructureType::query()->where('code', 'clinic')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.settings.structure-types.update', $type), [
                'code' => 'clinic',
                'name' => 'Clínica ambulatoria',
                'description' => 'Local clínico',
                'is_unit' => true,
                'is_active' => false,
                'sort_order' => 125,
            ])
            ->assertRedirect(route('admin.settings.structure-types.index'));

        $this->assertDatabaseHas('structure_types', [
            'code' => 'clinic',
            'name' => 'Clínica ambulatoria',
            'is_active' => 0,
        ]);
    }
}
