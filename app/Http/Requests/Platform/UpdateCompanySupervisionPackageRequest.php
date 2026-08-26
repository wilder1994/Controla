<?php

declare(strict_types=1);

namespace App\Http\Requests\Platform;

use App\Enums\SupervisionPackageSku;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateCompanySupervisionPackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('platform.companies.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'supervision_package_sku' => ['nullable', 'string', Rule::enum(SupervisionPackageSku::class)],
        ];
    }
}
