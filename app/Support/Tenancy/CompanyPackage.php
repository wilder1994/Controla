<?php

declare(strict_types=1);

namespace App\Support\Tenancy;

use App\Enums\CompanyPackageSku;
use App\Enums\PackageModality;
use App\Models\SecurityCompany;

final class CompanyPackage
{
    public static function modalityOf(SecurityCompany $company): PackageModality
    {
        if ($company->package_modality instanceof PackageModality) {
            return $company->package_modality;
        }

        if (is_string($company->package_modality) && $company->package_modality !== '') {
            return PackageModality::from($company->package_modality);
        }

        return PackageModality::Manual;
    }

    public static function allows(SecurityCompany $company, string $feature): bool
    {
        if ((int) ($company->package_hardware_seats ?? 0) > 0) {
            return PackageModality::Hardware->allows($feature)
                || PackageModality::Manual->allows($feature);
        }

        return self::modalityOf($company)->allows($feature);
    }

    public static function skuOf(SecurityCompany $company): ?CompanyPackageSku
    {
        if ($company->package_sku instanceof CompanyPackageSku) {
            return $company->package_sku;
        }

        if (is_string($company->package_sku) && $company->package_sku !== '') {
            return CompanyPackageSku::tryFrom($company->package_sku);
        }

        return null;
    }
}
