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
            ->assertSee('Tipos de estructura')
            ->assertDontSee('Es unidad ocupable')
            ->assertDontSee('Descripción');

        $this->actingAs($admin)
            ->post(route('admin.settings.structure-types.store'), [
                'name' => 'Clínica',
                'is_active' => true,
            ])
            ->assertRedirect(route('admin.settings.structure-types.index'));

        $type = StructureType::query()->where('name', 'Clínica')->firstOrFail();
        $this->assertSame('clinica', $type->code);
        $this->assertTrue($type->is_active);

        $this->actingAs($admin)
            ->put(route('admin.settings.structure-types.update', $type), [
                'name' => 'Clínica ambulatoria',
                'is_active' => false,
            ])
            ->assertRedirect(route('admin.settings.structure-types.index'));

        $this->assertDatabaseHas('structure_types', [
            'id' => $type->id,
            'code' => 'clinica',
            'name' => 'Clínica ambulatoria',
            'is_active' => 0,
        ]);
    }

    public function test_super_admin_can_reorder_structure_types(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@control-acceso.test')->firstOrFail();

        $first = StructureType::query()->create([
            'code' => 'alpha',
            'name' => 'Alpha',
            'is_active' => true,
            'is_unit' => false,
            'sort_order' => 10,
        ]);
        $second = StructureType::query()->create([
            'code' => 'beta',
            'name' => 'Beta',
            'is_active' => true,
            'is_unit' => false,
            'sort_order' => 20,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.settings.structure-types.move-up', $second))
            ->assertRedirect(route('admin.settings.structure-types.index'));

        $this->assertSame(10, $second->fresh()->sort_order);
        $this->assertSame(20, $first->fresh()->sort_order);
    }
}
