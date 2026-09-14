<?php

declare(strict_types=1);

namespace App\Http\Requests\Platform;

use App\Enums\BillingCycle;
use App\Enums\CompanyPackageSku;
use App\Enums\SupervisionPackageSku;
use App\Services\Platform\ApplyAdminPlanChangeService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ApplyAdminPlanChangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('platform.companies.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'package_sku' => ['required', Rule::enum(CompanyPackageSku::class)],
            'billing_cycle' => ['required', Rule::enum(BillingCycle::class)],
            'manual_seats' => ['nullable', 'integer', 'min:0'],
            'hardware_seats' => ['nullable', 'integer', 'min:0'],
            'supervision_package_sku' => ['nullable', 'string', Rule::enum(SupervisionPackageSku::class)],
            'apply_when' => ['required', Rule::in([
                ApplyAdminPlanChangeService::WHEN_NOW,
                ApplyAdminPlanChangeService::WHEN_ON_DATE,
                ApplyAdminPlanChangeService::WHEN_PERIOD_END,
            ])],
            'effective_on' => [
                'nullable',
                'date',
                'after_or_equal:today',
                Rule::requiredIf($this->input('apply_when') === ApplyAdminPlanChangeService::WHEN_ON_DATE),
            ],
        ];
    }
}
