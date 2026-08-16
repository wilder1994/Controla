<?php

declare(strict_types=1);

namespace App\Http\Requests\Platform;

use App\Enums\ManualPaymentIntent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreManualPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return ($user?->can('platform.documents.manage') || $user?->can('platform.companies.manage')) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'reference' => ['required', 'string', 'max:80'],
            'proof' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
            'intent' => ['required', Rule::enum(ManualPaymentIntent::class)],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'reference.required' => 'La referencia de consignación es obligatoria.',
            'proof.required' => 'Debe adjuntar el soporte de pago.',
            'proof.mimes' => 'El soporte debe ser PDF o imagen (jpg, png, webp).',
            'intent.required' => 'Seleccione el tipo de pago.',
        ];
    }
}
