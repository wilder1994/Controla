<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SupervisorFieldModule;
use App\Enums\SupervisorFieldOutcome;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SupervisorFieldLog extends Model
{
    protected $fillable = [
        'supervisor_shift_id',
        'security_company_id',
        'user_id',
        'client_id',
        'supervisor_recommendation_id',
        'module',
        'outcome',
        'payload',
        'notes',
        'latitude',
        'longitude',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'module' => SupervisorFieldModule::class,
            'outcome' => SupervisorFieldOutcome::class,
            'payload' => 'array',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'recorded_at' => 'datetime',
        ];
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(SupervisorShift::class, 'supervisor_shift_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(SecurityCompany::class, 'security_company_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function recommendation(): BelongsTo
    {
        return $this->belongsTo(SupervisorRecommendation::class, 'supervisor_recommendation_id');
    }
}
