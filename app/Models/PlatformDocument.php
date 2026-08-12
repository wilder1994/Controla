<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PlatformDocumentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformDocument extends Model
{
    protected $fillable = [
        'security_company_id',
        'type',
        'title',
        'reference_number',
        'amount',
        'is_demo',
        'cufe',
        'storage_path',
        'metadata',
        'issued_at',
        'retention_until',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => PlatformDocumentType::class,
            'amount' => 'decimal:2',
            'is_demo' => 'boolean',
            'metadata' => 'array',
            'issued_at' => 'datetime',
            'retention_until' => 'date',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(SecurityCompany::class, 'security_company_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
