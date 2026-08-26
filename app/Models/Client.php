<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ClientLifecycle;
use App\Enums\ClientPlanTier;
use App\Enums\PartyType;
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
        'party_type',
        'legal_name',
        'document_type',
        'tax_id',
        'email',
        'phone',
        'representative_name',
        'representative_email',
        'structure_type_id',
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
        'has_access',
        'has_supervision',
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
            'party_type' => PartyType::class,
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'plan_tier' => ClientPlanTier::class,
            'max_structures' => 'integer',
            'is_active' => 'boolean',
            'has_access' => 'boolean',
            'has_supervision' => 'boolean',
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

    public function structureType(): BelongsTo
    {
        return $this->belongsTo(StructureType::class);
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

    public function isCatalogOnly(): bool
    {
        return ! $this->has_access && ! $this->has_supervision;
    }

    public function supervisorShiftReviews(): HasMany
    {
        return $this->hasMany(SupervisorShiftReview::class);
    }

    public function supervisorFieldLogs(): HasMany
    {
        return $this->hasMany(SupervisorFieldLog::class);
    }
}
