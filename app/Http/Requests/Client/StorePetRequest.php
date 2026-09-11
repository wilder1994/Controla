<?php

declare(strict_types=1);

namespace App\Http\Requests\Client;

use App\Enums\PetSpecies;
use App\Models\StructurePet;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StorePetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', StructurePet::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $clientId = app(TenantContext::class)->clientId();
        $allowedIds = app(TenantContext::class)->installationIds();
        $structureRule = Rule::exists('structures', 'id')->where('client_id', $clientId);
        if ($allowedIds !== null) {
            $structureRule->whereIn('installation_id', $allowedIds);
        }

        return [
            'structure_id' => ['required', 'integer', $structureRule],
            'name' => ['required', 'string', 'max:50'],
            'species' => ['required', Rule::enum(PetSpecies::class)],
            'breed' => ['nullable', 'string', 'max:50'],
            'is_potentially_dangerous' => ['boolean'],
        ];
    }
}
