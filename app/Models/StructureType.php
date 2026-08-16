<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

final class StructureType extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'is_unit',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_unit' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function structures(): HasMany
    {
        return $this->hasMany(Structure::class);
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    /** @param Builder<self> $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('name');
    }

    public static function idByCode(string $code): int
    {
        $id = self::query()->where('code', $code)->value('id');

        if ($id === null) {
            throw new InvalidArgumentException("Structure type [{$code}] is not seeded.");
        }

        return (int) $id;
    }

    /** @return array<int, string> */
    public static function optionsForSelect(bool $activeOnly = true): array
    {
        $query = self::query()->orderBy('sort_order')->orderBy('name');

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->pluck('name', 'id')->all();
    }
}
