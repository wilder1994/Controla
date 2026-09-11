<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToClient;
use App\Models\Concerns\ProtectsMinorIdentity;
use App\Support\Privacy\MinorPersonalData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

final class StructureMember extends Model
{
    use BelongsToClient, ProtectsMinorIdentity, SoftDeletes;

    protected $fillable = [
        'client_id',
        'structure_id',
        'member_type_id',
        'first_name',
        'last_name',
        'document_type',
        'document_number',
        'birth_date',
        'minor_treatment_accepted_at',
        'phone_primary',
        'phone_secondary',
        'email',
        'has_app_access',
        'access_code',
        'photo_path',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'minor_treatment_accepted_at' => 'datetime',
            'has_app_access' => 'boolean',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    /** @param Builder<self> $query */
    public function scopeShareable(Builder $query): Builder
    {
        return $query->where(function (Builder $inner): void {
            $inner->whereNull('birth_date')
                ->orWhereDate('birth_date', '<=', now()->subYears(MinorPersonalData::AGE_OF_MAJORITY)->toDateString());
        });
    }

    public function structure(): BelongsTo
    {
        return $this->belongsTo(Structure::class);
    }

    public function memberType(): BelongsTo
    {
        return $this->belongsTo(MemberType::class);
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
