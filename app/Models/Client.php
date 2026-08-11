<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ClientLifecycle;
use App\Enums\ClientPlanTier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'security_company_id',
        'name',
        'slug',
        'login_suffix',
        'address',
        'city',
        'department',
        'latitude',
        'longitude',
        'plan_tier',
        'max_structures',
        'logo_path',
        'access_url',
        'is_active',
        'service_started_at',
        'service_hours',
        'revista_target_per_day',
        'lifecycle',
        'released_at',
        'archived_at',
        'tenant_data_purged_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'plan_tier' => ClientPlanTier::class,
            'max_structures' => 'integer',
            'is_active' => 'boolean',
            'service_started_at' => 'date',
            'service_hours' => 'integer',
            'revista_target_per_day' => 'integer',
            'lifecycle' => ClientLifecycle::class,
            'released_at' => 'datetime',
            'archived_at' => 'datetime',
            'tenant_data_purged_at' => 'datetime',
        ];
    }

    public function securityCompany(): BelongsTo
    {
        return $this->belongsTo(SecurityCompany::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ClientUserAssignment::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'client_user_assignments')
            ->withPivot(['is_primary', 'assigned_at'])
            ->withTimestamps();
    }

    public function loginDomain(): string
    {
        return '@'.$this->login_suffix;
    }
}
