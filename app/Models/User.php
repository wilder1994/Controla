<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'last_login_at',
        'must_change_password',
        'area_key',
        'security_company_id',
        'primary_client_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'must_change_password' => 'boolean',
        ];
    }

    public function securityCompany(): BelongsTo
    {
        return $this->belongsTo(SecurityCompany::class);
    }

    public function primaryClient(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'primary_client_id');
    }

    public function clientAssignments(): HasMany
    {
        return $this->hasMany(ClientUserAssignment::class);
    }

    public function clients(): BelongsToMany
    {
        return $this->belongsToMany(Client::class, 'client_user_assignments')
            ->withPivot(['is_primary', 'assigned_at'])
            ->withTimestamps();
    }

    /** @return list<int> */
    public function assignedClientIds(): array
    {
        if ($this->hasRole('super-admin')) {
            return Client::query()->pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        if ($this->hasRole('company-admin') && $this->security_company_id) {
            return Client::query()
                ->where('security_company_id', $this->security_company_id)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        return $this->clients()
            ->pluck('clients.id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function canAccessClient(int $clientId): bool
    {
        return in_array($clientId, $this->assignedClientIds(), true);
    }
}
