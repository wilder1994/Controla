<?php

declare(strict_types=1);

namespace App\Http\Requests\Public;

use App\Models\CommercialSignupIntent;
use App\Support\Legal\CorpusAcceptanceRules;
use Illuminate\Foundation\Http\FormRequest;

final class StoreSignupLegalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var CommercialSignupIntent $intent */
        $intent = $this->route('intent');

        return [
            'representative_name' => ['required', 'string', 'max:120'],
            'representative_role' => ['required', 'string', 'max:80'],
            'representative_document_type' => CorpusAcceptanceRules::documentTypeRule(),
            'representative_document_number' => ['required', 'string', 'max:40'],
            ...CorpusAcceptanceRules::acceptDocRules($intent->package_sku),
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
