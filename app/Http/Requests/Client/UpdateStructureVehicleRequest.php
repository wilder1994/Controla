<?php

declare(strict_types=1);

namespace App\Http\Requests\Client;

use App\Models\Vehicle;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateStructureVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $vehicle = $this->route('vehicle');

        return $vehicle instanceof Vehicle
            && ($this->user()?->can('update', $vehicle) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $clientId = app(TenantContext::class)->clientId();
        $vehicle = $this->route('vehicle');

        return [
            'structure_id' => ['required', 'integer', Rule::exists('structures', 'id')->where('client_id', $clientId)],
            'plate' => [
                'required',
                'string',
                'max:20',
                Rule::unique('vehicles', 'plate')
                    ->where('client_id', $clientId)
                    ->ignore($vehicle instanceof Vehicle ? $vehicle->id : null),
            ],
            'brand' => ['nullable', 'string', 'max:50'],
            'model' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:30'],
            'type' => ['nullable', 'string', 'max:20'],
            'assigned_parking_spot' => ['nullable', 'string', 'max:50'],
            'soat_expires_at' => ['nullable', 'date'],
            'license_expires_at' => ['nullable', 'date'],
            'is_visitor_vehicle' => ['boolean'],
        ];
    }
}
