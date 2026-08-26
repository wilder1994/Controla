<?php

declare(strict_types=1);

namespace App\Http\Requests\Company;

use App\Enums\BillingCycle;
use App\Enums\CompanyPackageSku;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SchedulePackageChangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('company.dashboard') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'package_sku' => ['required', Rule::enum(CompanyPackageSku::class)],
            'billing_cycle' => ['required', Rule::enum(BillingCycle::class)],
            'manual_seats' => ['nullable', 'integer', 'min:0'],
            'hardware_seats' => ['nullable', 'integer', 'min:0'],
            'supervision_package_sku' => ['nullable', 'string'],
        ];
    }
}
