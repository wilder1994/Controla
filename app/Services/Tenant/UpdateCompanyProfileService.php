<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Domain\Geo\GeoAddressData;
use App\Models\SecurityCompany;
use Illuminate\Validation\ValidationException;

final class UpdateCompanyProfileService
{
    public function execute(SecurityCompany $company, array $profileAttributes, GeoAddressData $geo): SecurityCompany
    {
        if ($company->hasCompletedAcceptance() && isset($profileAttributes['tax_id'])) {
            unset($profileAttributes['tax_id']);
        }

        $company->update(array_merge($profileAttributes, $geo->toModelAttributes()));

        return $company->fresh();
    }

    public function assertTaxIdImmutable(SecurityCompany $company, ?string $newTaxId): void
    {
        if ($newTaxId === null || $newTaxId === $company->tax_id) {
            return;
        }

        if ($company->hasCompletedAcceptance()) {
            throw ValidationException::withMessages([
                'tax_id' => 'El identificador fiscal no puede modificarse tras la aceptación contractual.',
            ]);
        }
    }
}
