<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MemberType;
use App\Models\Concerns\BelongsToClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

final class StructureMember extends Model
{
    use BelongsToClient, SoftDeletes;

    protected $fillable = [
        'client_id',
        'structure_id',
        'first_name',
        'last_name',
        'document_number',
        'phone_primary',
        'phone_secondary',
        'email',
        'member_type',
        'has_app_access',
        'access_code',
        'photo_path',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'member_type' => MemberType::class,
            'has_app_access' => 'boolean',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function structure(): BelongsTo
    {
        return $this->belongsTo(Structure::class);
    }

    public function authorizations(): HasMany
    {
        return $this->hasMany(VisitorPreAuthorization::class, 'member_id');
    }

    public function appUser(): HasOne
    {
        return $this->hasOne(StructureAppUser::class, 'member_id');
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}
