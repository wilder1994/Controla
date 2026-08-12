<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EvidenceEventType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LifecycleEvidenceEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'security_company_id',
        'client_id',
        'event_type',
        'title',
        'payload',
        'content_hash',
        'occurred_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'event_type' => EvidenceEventType::class,
            'payload' => 'array',
            'occurred_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(SecurityCompany::class, 'security_company_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
