<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StructureType;
use App\Models\Concerns\BelongsToClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Structure extends Model
{
    use BelongsToClient, SoftDeletes;

    protected $fillable = [
        'client_id',
        'parent_id',
        'name',
        'code',
        'type',
        'max_occupancy',
        'metadata',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => StructureType::class,
            'metadata' => 'array',
            'is_active' => 'boolean',
            'max_occupancy' => 'integer',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(StructureMember::class);
    }

    public function pets(): HasMany
    {
        return $this->hasMany(StructurePet::class);
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    public function authorizations(): HasMany
    {
        return $this->hasMany(VisitorPreAuthorization::class);
    }

    public function getFullPathAttribute(): string
    {
        $parts = [$this->name];
        $node = $this->parent;

        while ($node !== null) {
            array_unshift($parts, $node->name);
            $node = $node->parent;
        }

        return implode(' › ', $parts);
    }
}
