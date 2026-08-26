<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SupervisorShiftLocation extends Model
{
    protected $fillable = [
        'supervisor_shift_id',
        'recorded_at',
        'latitude',
        'longitude',
        'accuracy',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'accuracy' => 'decimal:2',
        ];
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(SupervisorShift::class, 'supervisor_shift_id');
    }
}
