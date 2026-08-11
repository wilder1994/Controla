<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToClient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

final class Blocklist extends Model
{
    use BelongsToClient;

    protected $table = 'blocklist';

    public const TYPE_VISITOR = 'visitor';

    public const TYPE_VEHICLE = 'vehicle';

    public const TYPE_RESIDENT = 'resident';

    /** @var list<string> */
    public const PERSON_TYPES = [
        self::TYPE_VISITOR,
        self::TYPE_RESIDENT,
        Visitor::class,
        Resident::class,
    ];

    /** @var list<string> */
    public const VEHICLE_TYPES = [
        self::TYPE_VEHICLE,
        Vehicle::class,
    ];

    protected $fillable = [
        'client_id',
        'reason',
        'blockable_type',
        'blockable_id',
        'blocked_by',
        'blocked_at',
        'expires_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'blocked_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function blockable(): MorphTo
    {
        return $this->morphTo();
    }

    public function blocker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_by');
    }

    /** @param  Builder<self>  $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(function (Builder $inner): void {
                $inner->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /** @param  Builder<self>  $query */
    public function scopeVehicles(Builder $query): Builder
    {
        return $query->whereIn('blockable_type', self::VEHICLE_TYPES);
    }

    /** @param  Builder<self>  $query */
    public function scopePersons(Builder $query): Builder
    {
        return $query->whereIn('blockable_type', self::PERSON_TYPES);
    }

    public function typeLabel(): string
    {
        return match ($this->normalizedType()) {
            self::TYPE_VEHICLE => 'Vehículo',
            self::TYPE_RESIDENT => 'Residente',
            self::TYPE_VISITOR => 'Visitante',
            default => class_basename((string) $this->blockable_type),
        };
    }

    public function isVehicle(): bool
    {
        return $this->normalizedType() === self::TYPE_VEHICLE;
    }

    public function isPerson(): bool
    {
        $type = $this->normalizedType();

        return $type === self::TYPE_VISITOR || $type === self::TYPE_RESIDENT;
    }

    public function normalizedType(): string
    {
        $raw = (string) $this->blockable_type;

        if (in_array($raw, self::VEHICLE_TYPES, true) || str_contains($raw, 'Vehicle')) {
            return self::TYPE_VEHICLE;
        }

        if (in_array($raw, [self::TYPE_RESIDENT, Resident::class], true) || str_contains($raw, 'Resident')) {
            return self::TYPE_RESIDENT;
        }

        if (in_array($raw, [self::TYPE_VISITOR, Visitor::class], true) || str_contains($raw, 'Visitor')) {
            return self::TYPE_VISITOR;
        }

        return $raw;
    }
}
