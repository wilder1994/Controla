<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ArchiveReason;
use App\Enums\BillingCycle;
use App\Enums\ClientLifecycle;
use App\Enums\CompanyPackageSku;
use App\Enums\PackageModality;
use App\Enums\PartyType;
use App\Enums\SubscriptionStatus;
use App\Support\Tenancy\CompanyPackage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SecurityCompany extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'legal_name',
        'trade_name',
        'tax_id',
        'party_type',
        'email',
        'phone',
        'address',
        'latitude',
        'longitude',
        'logo_path',
        'is_active',
        'package_size',
        'package_modality',
        'package_sku',
        'package_price_monthly',
        'max_clients',
        'billing_cycle',
        'billing_day',
        'unit_price_snapshot',
        'volume_discount_pct',
        'annual_discount_pct',
        'package_price_annual',
        'package_starts_at',
        'package_ends_at',
        'grace_ends_at',
        'suspended_at',
        'archived_at',
        'archive_reason',
        'commercial_anonymized_at',
        'subscription_status',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'package_size' => 'integer',
            'package_modality' => PackageModality::class,
            'party_type' => PartyType::class,
            'package_sku' => CompanyPackageSku::class,
            'package_price_monthly' => 'decimal:2',
            'max_clients' => 'integer',
            'billing_cycle' => BillingCycle::class,
            'billing_day' => 'integer',
            'unit_price_snapshot' => 'decimal:2',
            'volume_discount_pct' => 'decimal:4',
            'annual_discount_pct' => 'decimal:4',
            'package_price_annual' => 'decimal:2',
            'package_starts_at' => 'datetime',
            'package_ends_at' => 'datetime',
            'grace_ends_at' => 'datetime',
            'suspended_at' => 'datetime',
            'archived_at' => 'datetime',
            'archive_reason' => ArchiveReason::class,
            'commercial_anonymized_at' => 'datetime',
            'subscription_status' => SubscriptionStatus::class,
        ];
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function subscriptionAcceptances(): HasMany
    {
        return $this->hasMany(SubscriptionAcceptance::class);
    }

    public function commercialPayments(): HasMany
    {
        return $this->hasMany(CommercialPayment::class);
    }

    public function platformDocuments(): HasMany
    {
        return $this->hasMany(PlatformDocument::class);
    }

    public function lifecycleEvidenceEvents(): HasMany
    {
        return $this->hasMany(LifecycleEvidenceEvent::class);
    }

    public function latestAcceptance(): ?SubscriptionAcceptance
    {
        return $this->subscriptionAcceptances()->latest('accepted_at')->first();
    }

    public function hasCompletedAcceptance(): bool
    {
        return $this->subscriptionAcceptances()->exists();
    }

    public function displayName(): string
    {
        return $this->trade_name ?: $this->legal_name;
    }

    public function activeClients(): HasMany
    {
        return $this->clients()->where('is_active', true);
    }

    public function packageLabel(): string
    {
        $sku = CompanyPackage::skuOf($this);

        return $sku?->label() ?? 'Sin paquete asignado';
    }

    public function allowsFeature(string $feature): bool
    {
        return CompanyPackage::allows($this, $feature);
    }

    public function clientsRemaining(): int
    {
        $max = (int) ($this->max_clients ?: 0);

        return max(0, $max - $this->operationalClientsCount());
    }

    public function operationalClientsCount(): int
    {
        return $this->clients()
            ->where('lifecycle', ClientLifecycle::Active)
            ->count();
    }

    public function contractedAmount(): float
    {
        if ($this->billing_cycle === BillingCycle::Annual) {
            return (float) ($this->package_price_annual ?? 0);
        }

        return (float) ($this->package_price_monthly ?? 0);
    }

    public function billingPeriodLabel(): string
    {
        return $this->billing_cycle?->label() ?? 'Mensual';
    }
}
