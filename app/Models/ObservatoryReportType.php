<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ObservatoryReportType extends Model
{
    protected $fillable = [
        'client_id',
        'name',
        'slug',
        'level',
        'color',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(ObservatoryReport::class, 'observatory_report_type_id');
    }

    public function levelLabel(): string
    {
        return 'Nivel '.$this->level;
    }

    /** @return array<string, string> */
    public static function optionsFor(int $clientId, bool $activeOnly = true): array
    {
        return static::query()
            ->where('client_id', $clientId)
            ->when($activeOnly, fn ($q) => $q->where('is_active', true))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (self $type): array => [$type->slug => $type->name])
            ->all();
    }

    /** @return list<array{id: int, slug: string, name: string, level: int, color: string}> */
    public static function catalogFor(int $clientId, bool $activeOnly = true): array
    {
        return static::query()
            ->where('client_id', $clientId)
            ->when($activeOnly, fn ($q) => $q->where('is_active', true))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (self $type): array => [
                'id' => (int) $type->id,
                'slug' => $type->slug,
                'name' => $type->name,
                'level' => (int) $type->level,
                'color' => $type->color,
            ])
            ->all();
    }
}
