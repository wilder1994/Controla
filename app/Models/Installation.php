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
    ];

    protected function casts(): array
    {
        return [
            'is_client_site' => 'boolean',
            'is_active' => 'boolean',
        ];
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
