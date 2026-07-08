<?php

declare(strict_types=1);

namespace App\Http\Requests\Client;

use App\Models\Vehicle;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreStructureVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('client.vehicles.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $clientId = app(TenantContext::class)->clientId();

        return [
            'structure_id' => ['required', 'integer', Rule::exists('structures', 'id')->where('client_id', $clientId)],
            'plate' => ['required', 'string', 'max:20'],
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
