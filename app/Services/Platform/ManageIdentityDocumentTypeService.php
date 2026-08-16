<?php

declare(strict_types=1);

namespace App\Services\Platform;

use App\Models\IdentityDocumentType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ManageIdentityDocumentTypeService
{
    /** @param array{name: string, is_active?: bool} $data */
    public function create(array $data): IdentityDocumentType
    {
        $name = trim($data['name']);

        return IdentityDocumentType::query()->create([
            'code' => $this->uniqueCodeFromName($name),
            'name' => $name,
            'is_active' => (bool) ($data['is_active'] ?? true),
            'sort_order' => $this->nextSortOrder(),
        ]);
    }

    /** @param array{name?: string, is_active?: bool} $data */
    public function update(IdentityDocumentType $type, array $data): IdentityDocumentType
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

    public function moveUp(IdentityDocumentType $type): void
    {
        $this->swapWithNeighbor($type, 'up');
    }

    public function moveDown(IdentityDocumentType $type): void
    {
        $this->swapWithNeighbor($type, 'down');
    }

    public function delete(IdentityDocumentType $type): void
    {
        DB::transaction(static function () use ($type): void {
            $type->delete();
        });
    }

    private function nextSortOrder(): int
    {
        return ((int) IdentityDocumentType::query()->max('sort_order')) + 10;
    }

    private function uniqueCodeFromName(string $name, ?int $ignoreId = null): string
    {
        $base = Str::upper(Str::slug($name, ''));
        if ($base === '') {
            $base = 'DOC';
        }

        $base = Str::limit($base, 16, '');
        $code = $base;
        $suffix = 2;

        while ($this->codeExists($code, $ignoreId)) {
            $code = Str::limit($base, 14, '').$suffix;
            $suffix++;
        }

        return $code;
    }

    private function codeExists(string $code, ?int $ignoreId = null): bool
    {
        $query = IdentityDocumentType::query()->where('code', $code);

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    private function swapWithNeighbor(IdentityDocumentType $type, string $direction): void
    {
        DB::transaction(function () use ($type, $direction): void {
            $ordered = IdentityDocumentType::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->lockForUpdate()
                ->get();

            $index = $ordered->search(fn (IdentityDocumentType $row) => $row->id === $type->id);
            if ($index === false) {
                return;
            }

            $neighborIndex = $direction === 'up' ? $index - 1 : $index + 1;
            if ($neighborIndex < 0 || $neighborIndex >= $ordered->count()) {
                return;
            }

            /** @var IdentityDocumentType $neighbor */
            $neighbor = $ordered[$neighborIndex];
            $currentOrder = $type->sort_order;
            $type->update(['sort_order' => $neighbor->sort_order]);
            $neighbor->update(['sort_order' => $currentOrder]);
        });
    }
}
