<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\StructureType;
use Illuminate\Database\Seeder;

final class StructureTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['code' => 'general_area', 'name' => 'Conjunto / Zona', 'description' => 'Raíz del sitio o zona general', 'is_unit' => false, 'sort_order' => 10],
            ['code' => 'ph', 'name' => 'Propiedad horizontal', 'description' => 'Conjunto tipo PH', 'is_unit' => false, 'sort_order' => 20],
            ['code' => 'industrial_zone', 'name' => 'Zona industrial', 'description' => 'Parque o zona industrial', 'is_unit' => false, 'sort_order' => 30],
            ['code' => 'block', 'name' => 'Torre / Bloque', 'description' => 'Agrupación vertical u horizontal', 'is_unit' => false, 'sort_order' => 40],
            ['code' => 'apartment', 'name' => 'Apartamento', 'description' => 'Unidad habitacional', 'is_unit' => true, 'sort_order' => 50],
            ['code' => 'house', 'name' => 'Casa', 'description' => 'Unidad habitacional independiente', 'is_unit' => true, 'sort_order' => 60],
            ['code' => 'office', 'name' => 'Oficina', 'description' => 'Unidad de oficina', 'is_unit' => true, 'sort_order' => 70],
            ['code' => 'commercial_store', 'name' => 'Local comercial', 'description' => 'Local o tienda', 'is_unit' => true, 'sort_order' => 80],
            ['code' => 'warehouse', 'name' => 'Bodega', 'description' => 'Bodega o depósito', 'is_unit' => true, 'sort_order' => 90],
            ['code' => 'pharmacy', 'name' => 'Farmacia', 'description' => 'Local farmacéutico', 'is_unit' => true, 'sort_order' => 100],
        ];

        foreach ($types as $type) {
            StructureType::query()->updateOrCreate(
                ['code' => $type['code']],
                [
                    ...$type,
                    'is_active' => true,
                ]
            );
        }
    }
}
