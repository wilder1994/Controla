<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SupervisorRecommendationPriority;
use App\Enums\SupervisorRecommendationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class SupervisorRecommendation extends Model
{
    protected $fillable = [
        'security_company_id',
        'client_id',
        'opened_by_user_id',
        'opened_shift_id',
        'closed_by_user_id',
        'closed_shift_id',
        'status',
        'priority',
        'due_date',
        'title',
        'body',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => SupervisorRecommendationStatus::class,
            'priority' => SupervisorRecommendationPriority::class,
            'due_date' => 'date',
            'closed_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(SecurityCompany::class, 'security_company_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by_user_id');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by_user_id');
    }

    public function openedShift(): BelongsTo
    {
        return $this->belongsTo(SupervisorShift::class, 'opened_shift_id');
    }

    public function fieldLogs(): HasMany
    {
        return $this->hasMany(SupervisorFieldLog::class);
    }

    public function isOverdue(): bool
    {
        return $this->status !== SupervisorRecommendationStatus::Closed
            && $this->due_date !== null
            && $this->due_date->isPast();
    }
}
