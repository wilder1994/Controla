<?php

declare(strict_types=1);

namespace App\Http\Requests\Client;

use App\Models\Structure;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreStructureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Structure::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $clientId = app(TenantContext::class)->clientId();

        $installationId = $this->integer('installation_id') ?: null;
        $allowedIds = app(TenantContext::class)->installationIds();

        $parentExists = Rule::exists('structures', 'id')->where('client_id', $clientId);
        if ($installationId !== null) {
            $parentExists->where('installation_id', $installationId);
        }

        $installationRule = [
            'required',
            'integer',
            Rule::exists('installations', 'id')->where('client_id', $clientId),
        ];
        if ($allowedIds !== null) {
            $installationRule[] = Rule::in($allowedIds);
        }

        return [
            'installation_id' => $installationRule,
            'parent_id' => ['nullable', 'integer', $parentExists],
            'name' => ['required', 'string', 'max:100'],
            'max_occupancy' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }
}
