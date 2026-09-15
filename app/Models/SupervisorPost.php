<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class SupervisorPost extends Model
{
    use BelongsToClient, SoftDeletes;

    protected $fillable = [
        'client_id',
        'installation_id',
        'name',
        'modality',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'modality' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function modalityLabel(): string
    {
        $hours = (int) ($this->modality ?? 0);
        if ($hours < 1) {
            return '—';
        }

        $companyId = (int) ($this->client?->security_company_id ?? 0);
        if ($companyId > 0) {
            $row = SupervisorPostModality::query()
                ->where('security_company_id', $companyId)
                ->where('hours', $hours)
                ->first();
            if ($row !== null) {
                return $row->label();
            }
        }

        return $hours.' h';
    }

    public function installation(): BelongsTo
    {
        return $this->belongsTo(Installation::class);
    }

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'supervisor_post_employee')
            ->withTimestamps();
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(SupervisorShiftReview::class);
    }
}
