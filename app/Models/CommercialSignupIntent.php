<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BillingCycle;
use App\Enums\CompanyPackageSku;
use App\Enums\PartyType;
use App\Enums\SignupIntentStatus;
use Illuminate\Database\Eloquent\Model;

class CommercialSignupIntent extends Model
{
    protected $fillable = [
        'token',
        'status',
        'package_sku',
        'billing_cycle',
        'amount',
        'currency',
        'party_type',
        'legal_name',
        'trade_name',
        'tax_id',
        'admin_name',
        'email',
        'phone',
        'address',
        'city',
        'department',
        'latitude',
        'longitude',
        'password',
        'representative_name',
        'representative_role',
        'representative_document_type',
        'representative_document_number',
        'corpus_snapshot',
        'content_hash',
        'ip_address',
        'user_agent',
        'expires_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => SignupIntentStatus::class,
            'package_sku' => CompanyPackageSku::class,
            'billing_cycle' => BillingCycle::class,
            'party_type' => PartyType::class,
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'amount' => 'decimal:2',
            'corpus_snapshot' => 'array',
            'expires_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'token';
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isCheckoutReady(): bool
    {
        return $this->status === SignupIntentStatus::AwaitingPayment
            && ! $this->isExpired()
            && $this->password !== null
            && $this->corpus_snapshot !== null
            && $this->content_hash !== null;
    }

    public function packageLabel(): string
    {
        return $this->package_sku?->label() ?? '';
    }
}
