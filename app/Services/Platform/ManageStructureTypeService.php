<?php

declare(strict_types=1);

namespace App\Services\Platform;

use App\Models\StructureType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ManageStructureTypeService
{
    /** @param array{name: string, is_active?: bool} $data */
    public function create(array $data): StructureType
    {
        $name = trim($data['name']);

        return StructureType::query()->create([
            'code' => $this->uniqueCodeFromName($name),
            'name' => $name,
            'description' => null,
            'is_unit' => false,
            'is_active' => (bool) ($data['is_active'] ?? true),
            'sort_order' => $this->nextSortOrder(),
        ]);
    }

    /** @param array{name?: string, is_active?: bool} $data */
    public function update(StructureType $type, array $data): StructureType
    {
        $payload = [];

        if (array_key_exists('name', $data) && is_string($data['name'])) {
            $payload['name'] = trim($data['name']);
        }

        if (array_key_exists('is_active', $data)) {
            $payload['is_active'] = (bool) $data['is_active'];
        }

        if ($payload !== []) {
            $type->update($payload);
        }

        return $type->refresh();
    }

    public function moveUp(StructureType $type): void
    {
        $this->swapWithNeighbor($type, direction: 'up');
    }

    public function moveDown(StructureType $type): void
    {
        $this->swapWithNeighbor($type, direction: 'down');
    }

    public function delete(StructureType $type): void
    {
        if ($type->structures()->exists() || $type->clients()->exists()) {
            throw ValidationException::withMessages([
                'structure_type' => 'No se puede eliminar: hay clientes o estructuras usando este tipo.',
            ]);
        }

        DB::transaction(static function () use ($type): void {
            $type->delete();
        });
    }

    private function nextSortOrder(): int
    {
        return ((int) StructureType::query()->max('sort_order')) + 10;
    }

    private function uniqueCodeFromName(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name, '_');
        if ($base === '') {
            $base = 'tipo';
        }

        $base = Str::limit($base, 45, '');
        $code = $base;
        $suffix = 2;

        while ($this->codeExists($code, $ignoreId)) {
            $code = Str::limit($base, 40, '').'_'.$suffix;
            $suffix++;
        }

        return $code;
    }

    private function codeExists(string $code, ?int $ignoreId = null): bool
    {
        $query = StructureType::query()->where('code', $code);

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    private function swapWithNeighbor(StructureType $type, string $direction): void
    {
        DB::transaction(function () use ($type, $direction): void {
            $ordered = StructureType::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->lockForUpdate()
                ->get();

            $index = $ordered->search(fn (StructureType $row) => $row->id === $type->id);
            if ($index === false) {
                return;
            }

            $neighborIndex = $direction === 'up' ? $index - 1 : $index + 1;
            if ($neighborIndex < 0 || $neighborIndex >= $ordered->count()) {
                return;
            }

            /** @var StructureType $neighbor */
            $neighbor = $ordered[$neighborIndex];
            $currentOrder = $type->sort_order;
            $type->update(['sort_order' => $neighbor->sort_order]);
            $neighbor->update(['sort_order' => $currentOrder]);
        });
    }
}
