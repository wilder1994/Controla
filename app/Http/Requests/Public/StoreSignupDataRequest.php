<?php

declare(strict_types=1);

namespace App\Http\Requests\Public;

use App\Enums\PartyType;
use App\Support\Geo\GeoAddressRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class StoreSignupDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $partyType = $this->input('party_type', PartyType::LegalEntity->value);

        return [
            'party_type' => ['required', Rule::enum(PartyType::class)],
            'legal_name' => ['required', 'string', 'max:160'],
            'trade_name' => ['nullable', 'string', 'max:160'],
            'tax_id' => ['required', 'string', 'max:40', Rule::unique('security_companies', 'tax_id')],
            'admin_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'max:40'],
            ...GeoAddressRules::optional(),
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }
}
