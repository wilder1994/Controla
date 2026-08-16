<?php

declare(strict_types=1);

namespace App\Http\Requests\Platform;

use App\Enums\BillingCycle;
use App\Enums\CompanyPackageSku;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SchedulePackageChangeRequest extends FormRequest
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
            'reference' => ['required', 'string', 'max:80'],
            'proof' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
