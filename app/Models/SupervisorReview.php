<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SupervisorReview extends Model
{
    use BelongsToClient;

    protected $fillable = [
        'client_id',
        'supervisor_id',
        'guard_user_id',
        'location_id',
        'guard_shift_id',
        'observations',
        'latitude',
        'longitude',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'reviewed_at' => 'datetime',
        ];
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function guardUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guard_user_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function guardShift(): BelongsTo
    {
        return $this->belongsTo(GuardShift::class);
    }
}
