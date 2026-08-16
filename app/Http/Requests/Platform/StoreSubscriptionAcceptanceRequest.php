<?php

declare(strict_types=1);

namespace App\Http\Requests\Platform;

use App\Models\SecurityCompany;
use App\Support\Legal\CorpusAcceptanceRules;
use Illuminate\Foundation\Http\FormRequest;

final class StoreSubscriptionAcceptanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('platform.documents.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var SecurityCompany $company */
        $company = $this->route('company');

        return [
            'representative_name' => ['required', 'string', 'max:120'],
            'representative_role' => ['required', 'string', 'max:80'],
            'representative_document_type' => CorpusAcceptanceRules::documentTypeRule(),
            'representative_document_number' => ['required', 'string', 'max:40'],
            ...CorpusAcceptanceRules::acceptDocRules($company->package_sku),
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'accept_docs.*.accepted' => 'Debe aceptar todos los documentos del corpus.',
        ];
    }
}
