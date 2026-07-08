<?php

declare(strict_types=1);

namespace App\Models;

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
        'plan_tier',
        'max_structures',
        'logo_path',
        'access_url',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'plan_tier' => ClientPlanTier::class,
            'max_structures' => 'integer',
            'is_active' => 'boolean',
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
