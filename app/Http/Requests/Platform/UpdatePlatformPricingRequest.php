<?php

declare(strict_types=1);

namespace App\Http\Requests\Platform;

use App\Enums\CommercialMetal;
use App\Enums\CommercialProduct;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdatePlatformPricingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('platform.companies.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $metals = array_column(CommercialMetal::cases(), 'value');
        $moduleKeys = array_keys(\App\Support\Catalog\CatalogSettings::defaults()['module_options'] ?? []);

        $rules = [
            'units.access' => ['required', 'numeric', 'min:1000'],
            'units.supervision' => ['required', 'numeric', 'min:0'],
            'units.indexing' => ['required', 'numeric', 'min:0'],
            'units.indexing_addon' => ['required', 'numeric', 'min:0'],
        ];

        foreach ([CommercialProduct::Access, CommercialProduct::Supervision, CommercialProduct::Indexing] as $product) {
            foreach ($metals as $metal) {
                $rules["discounts.{$product->value}.{$metal}"] = ['required', 'numeric', 'min:0', 'max:90'];
            }
            $rules["modules.{$product->value}"] = ['nullable', 'array'];
            $rules["modules.{$product->value}.*"] = ['string', Rule::in($moduleKeys)];
        }

        foreach ($metals as $metal) {
            $rules["observatory.{$metal}"] = ['required', 'numeric', 'min:0'];
        }

        $rules['modules.observatory'] = ['nullable', 'array'];
        $rules['modules.observatory.*'] = ['string', Rule::in($moduleKeys)];

        return $rules;
    }
}
