<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BloodGroup;
use App\Enums\Sex;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class Employee extends Model
{
    protected $fillable = [
        'security_company_id',
        'job_title_id',
        'document_type',
        'document_number',
        'last_name_paternal',
        'last_name_maternal',
        'first_names',
        'sex',
        'birth_date',
        'collaborator_type_id',
        'email',
        'nationality',
        'blood_group',
        'birth_department',
        'birth_city',
        'emergency_phone',
        'emergency_contact',
        'has_disability',
        'document_issue_department',
        'document_issue_city',
        'document_issued_at',
        'same_cost_center',
        'is_active',
        'ceased_at',
    ];

    protected function casts(): array
    {
        return [
            'sex' => Sex::class,
            'birth_date' => 'date',
            'blood_group' => BloodGroup::class,
            'has_disability' => 'boolean',
            'document_issued_at' => 'date',
            'same_cost_center' => 'boolean',
            'is_active' => 'boolean',
            'ceased_at' => 'date',
        ];
    }

    public function securityCompany(): BelongsTo
    {
        return $this->belongsTo(SecurityCompany::class);
    }

    public function jobTitle(): BelongsTo
    {
        return $this->belongsTo(CompanyJobTitle::class, 'job_title_id');
    }

    public function collaboratorType(): BelongsTo
    {
        return $this->belongsTo(CompanyCollaboratorType::class, 'collaborator_type_id');
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function fullName(): string
    {
        return trim(preg_replace('/\s+/u', ' ', "{$this->first_names} {$this->last_name_paternal} {$this->last_name_maternal}") ?? '');
    }

    public function age(): ?int
    {
        return $this->birth_date?->age;
    }
}
