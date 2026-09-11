<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Installation extends Model
{
    use BelongsToClient, SoftDeletes;

    protected $fillable = [
        'client_id',
        'name',
        'is_client_site',
        'is_active',
        'address',
        'city',
        'department',
        'latitude',
        'longitude',
    ];

    protected function casts(): array
    {
        return [
            'is_client_site' => 'boolean',
            'is_active' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    public function supervisorPosts(): HasMany
    {
        return $this->hasMany(SupervisorPost::class);
    }
}
