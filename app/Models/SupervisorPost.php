<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class SupervisorPost extends Model
{
    use BelongsToClient, SoftDeletes;

    protected $fillable = [
        'client_id',
        'installation_id',
        'name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function installation(): BelongsTo
    {
        return $this->belongsTo(Installation::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(SupervisorShiftReview::class);
    }
}
