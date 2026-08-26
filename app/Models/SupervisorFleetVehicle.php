<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class SupervisorFleetVehicle extends Model
{
    protected $fillable = [
        'security_company_id',
        'registered_by_user_id',
        'plate',
        'brand',
        'line',
        'model',
        'color',
        'type',
        'soat_expires_at',
        'technical_review_expires_at',
        'last_km',
    ];

    protected function casts(): array
    {
        return [
            'soat_expires_at' => 'date',
            'technical_review_expires_at' => 'date',
            'last_km' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(SecurityCompany::class, 'security_company_id');
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(SupervisorShift::class);
    }

    public function displayName(): string
    {
        $parts = array_filter([$this->plate, $this->brand, $this->line, $this->model]);

        return implode(' · ', $parts);
    }

    public static function normalizePlate(string $plate): string
    {
        return strtoupper(preg_replace('/\s+/', '', $plate) ?? $plate);
    }
}
