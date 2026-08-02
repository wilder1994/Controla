<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionAcceptance extends Model
{
    protected $fillable = [
        'security_company_id',
        'user_id',
        'representative_name',
        'representative_role',
        'representative_document_type',
        'representative_document_number',
        'corpus_snapshot',
        'content_hash',
        'ip_address',
        'user_agent',
        'accepted_at',
    ];

    protected function casts(): array
    {
        return [
            'corpus_snapshot' => 'array',
            'accepted_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(SecurityCompany::class, 'security_company_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
