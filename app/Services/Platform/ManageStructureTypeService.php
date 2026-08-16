<?php

declare(strict_types=1);

namespace App\Services\Platform;

use App\Models\StructureType;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ManageStructureTypeService
{
    /** @param array{code: string, name: string, description?: ?string, is_unit?: bool, is_active?: bool, sort_order?: int} $data */
    public function create(array $data): StructureType
    {
        return StructureType::query()->create([
            'code' => $data['code'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_unit' => (bool) ($data['is_unit'] ?? false),
            'is_active' => (bool) ($data['is_active'] ?? true),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);
    }

    /** @param array{code?: string, name?: string, description?: ?string, is_unit?: bool, is_active?: bool, sort_order?: int} $data */
    public function update(StructureType $type, array $data): StructureType
    {
        $type->update([
            'code' => $data['code'] ?? $type->code,
            'name' => $data['name'] ?? $type->name,
            'description' => array_key_exists('description', $data) ? $data['description'] : $type->description,
            'is_unit' => array_key_exists('is_unit', $data) ? (bool) $data['is_unit'] : $type->is_unit,
            'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : $type->is_active,
            'sort_order' => array_key_exists('sort_order', $data) ? (int) $data['sort_order'] : $type->sort_order,
        ]);

        return $type->refresh();
    }

    public function delete(StructureType $type): void
    {
        if ($type->structures()->exists()) {
            throw ValidationException::withMessages([
                'structure_type' => 'No se puede eliminar: hay estructuras usando este tipo.',
            ]);
        }

        DB::transaction(static function () use ($type): void {
            $type->delete();
        });
    }
}
