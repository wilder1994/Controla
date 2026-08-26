<?php

declare(strict_types=1);

namespace App\Http\Requests\Company;

use App\Models\CompanyJobTitle;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateCompanyJobTitleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $jobTitle = $this->route('jobTitle');

        return $jobTitle instanceof CompanyJobTitle
            && ($this->user()?->can('update', $jobTitle) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['name' => 'nombre'];
    }
}
