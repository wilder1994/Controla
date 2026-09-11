<?php

declare(strict_types=1);

namespace App\Http\Requests\Company;

use App\Models\Client;
use App\Support\Geo\GeoAddressRules;
use Illuminate\Foundation\Http\FormRequest;

final class StoreClientInstallationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $client = $this->route('client');

        return $client instanceof Client
            && ($this->user()?->can('update', $client) ?? false);
    }

    protected function prepareForValidation(): void
    {
        if (! $this->boolean('is_client_site')) {
            return;
        }

        $client = $this->route('client');
        if ($client instanceof Client) {
            $this->merge(['name' => $client->name]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $geo = $this->boolean('is_client_site')
            ? GeoAddressRules::optional()
            : GeoAddressRules::required();

        return [
            'name' => ['required', 'string', 'max:120'],
            'is_client_site' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'vista' => ['nullable', 'in:sitio,puertas,accesos,supervision'],
            ...$geo,
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'is_client_site' => 'la instalación es el mismo cliente',
            'address' => 'dirección',
            'city' => 'ciudad',
            'department' => 'departamento',
            'latitude' => 'ubicación en el mapa',
            'longitude' => 'ubicación en el mapa',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'latitude.required' => 'Fija la ubicación de la instalación en el mapa.',
            'longitude.required' => 'Fija la ubicación de la instalación en el mapa.',
        ];
    }
}
