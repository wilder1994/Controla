<?php

declare(strict_types=1);

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;

final class UpdatePlatformPricingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('platform.companies.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'unit_price_manual' => ['required', 'numeric', 'min:1000'],
            'unit_price_hardware' => ['required', 'numeric', 'min:1000'],
            'unit_price_supervision' => ['required', 'numeric', 'min:0'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'unit_price_manual.min' => 'El precio unitario manual debe ser al menos $1.000.',
            'unit_price_hardware.min' => 'El precio unitario con hardware debe ser al menos $1.000.',
            'unit_price_supervision.min' => 'El unitario de Supervisión Pro no puede ser negativo.',
        ];
    }
}
