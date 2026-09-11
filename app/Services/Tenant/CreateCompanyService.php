<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Domain\Geo\GeoAddressData;
use App\Enums\BillingCycle;
use App\Enums\CompanyPackageSku;
use App\Enums\PartyType;
use App\Enums\SupervisionPackageSku;
use App\Models\SecurityCompany;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CreateCompanyService
{
    public function __construct(
        private readonly AssignCompanyPackageService $assignCompanyPackageService,
        private readonly AssignCompanySupervisionPackageService $assignCompanySupervisionPackageService,
    ) {}

    /**
     * @param  array{
     *     legal_name: string,
     *     trade_name: string,
     *     tax_id: string,
     *     party_type: string|PartyType,
     *     email: string,
     *     phone: string,
     *     package_sku: string,
     *     billing_cycle: string,
     *     supervision_package_sku?: string|null,
     * }  $attributes
     */
    public function execute(array $attributes, GeoAddressData $geo): SecurityCompany
    {
        if (SecurityCompany::query()->where('tax_id', $attributes['tax_id'])->exists()) {
            throw ValidationException::withMessages([
                'tax_id' => 'Ya existe una empresa con este identificador fiscal.',
            ]);
        }

        return DB::transaction(function () use ($attributes, $geo): SecurityCompany {
            $partyType = $attributes['party_type'] instanceof PartyType
                ? $attributes['party_type']
                : PartyType::from((string) $attributes['party_type']);

            $company = SecurityCompany::query()->create([
                'legal_name' => $attributes['legal_name'],
                'trade_name' => $attributes['trade_name'] ?: $attributes['legal_name'],
                'tax_id' => $attributes['tax_id'],
                'party_type' => $partyType,
                'email' => $attributes['email'] ?? null,
                'phone' => $attributes['phone'] ?? null,
                ...$geo->toModelAttributes(),
                'is_active' => true,
            ]);

            $sku = CompanyPackageSku::from((string) $attributes['package_sku']);
            $cycle = BillingCycle::from((string) $attributes['billing_cycle']);
            $this->assignCompanyPackageService->execute($company, $sku, $cycle);

            $supervisionValue = $attributes['supervision_package_sku'] ?? null;
            if ($sku->allowsSupervision() && filled($supervisionValue)) {
                $this->assignCompanySupervisionPackageService->execute(
                    $company,
                    SupervisionPackageSku::from((string) $supervisionValue),
                );
            }

            return $company->fresh();
        });
    }
}
