<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Domain\Geo\GeoAddressData;
use App\Models\SecurityCompany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

final class UpdateCompanyProfileService
{
    /**
     * @param  array<string, mixed>  $profileAttributes
     */
    public function execute(
        SecurityCompany $company,
        array $profileAttributes,
        GeoAddressData $geo,
        ?UploadedFile $logo = null,
        bool $removeLogo = false,
    ): SecurityCompany {
        if ($company->hasCompletedAcceptance() && isset($profileAttributes['tax_id'])) {
            unset($profileAttributes['tax_id']);
        }

        unset($profileAttributes['logo'], $profileAttributes['remove_logo']);

        if (array_key_exists('field_sheet_intro', $profileAttributes)) {
            $intro = trim((string) $profileAttributes['field_sheet_intro']);
            $profileAttributes['field_sheet_intro'] = $intro !== '' ? $intro : null;
        }

        $company->update(array_merge($profileAttributes, $geo->toModelAttributes()));

        if ($removeLogo) {
            $this->deleteLogo($company);
            $company->update(['logo_path' => null]);
        } elseif ($logo !== null) {
            $this->storeLogo($company, $logo);
        }

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

    private function storeLogo(SecurityCompany $company, UploadedFile $logo): void
    {
        $this->deleteLogo($company);
        $ext = $logo->guessExtension() ?: 'jpg';
        $path = $logo->storeAs('companies/'.$company->id, 'logo.'.$ext, 'local');
        $company->update(['logo_path' => $path ?: null]);
    }

    private function deleteLogo(SecurityCompany $company): void
    {
        if ($company->logo_path && Storage::disk('local')->exists($company->logo_path)) {
            Storage::disk('local')->delete($company->logo_path);
        }
    }
}
