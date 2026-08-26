<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

final class IdentityDocumentType extends Model
{
    protected $fillable = [
        'code',
        'name',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @param Builder<self> $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('name');
    }

    /** @return array<string, string> code => name */
    public static function optionsForSelect(): array
    {
        return self::query()
            ->active()
            ->pluck('name', 'code')
            ->all();
    }

    public static function resolveActiveCode(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        $found = self::query()
            ->active()
            ->where(function ($query) use ($raw): void {
                $query->whereRaw('LOWER(code) = ?', [mb_strtolower($raw)])
                    ->orWhereRaw('LOWER(name) = ?', [mb_strtolower($raw)]);
            })
            ->first();

        return $found?->code;
    }

    public static function assertActiveCode(string $code): void
    {
        $exists = self::query()->where('code', $code)->where('is_active', true)->exists();

        if (! $exists) {
            throw new InvalidArgumentException("Identity document type [{$code}] is not active.");
        }
    }
}
