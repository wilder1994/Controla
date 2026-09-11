<?php

declare(strict_types=1);

namespace App\Http\Requests\Client;

use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreAppUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('client.app_users.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $clientId = app(TenantContext::class)->clientId();
        $allowedIds = app(TenantContext::class)->installationIds();

        $memberRule = Rule::exists('structure_members', 'id')->where(function ($query) use ($clientId, $allowedIds): void {
            $query->where('client_id', $clientId);
            if ($allowedIds !== null) {
                $query->whereIn('structure_id', function ($sub) use ($allowedIds): void {
                    $sub->select('id')->from('structures')->whereIn('installation_id', $allowedIds);
                });
            }
        });

        return [
            'member_id' => ['required', 'integer', $memberRule],
            'username' => ['required', 'string', 'max:80', 'alpha_dash'],
            'email' => ['nullable', 'email', 'max:150'],
            'password' => ['required', 'string', 'min:8'],
            'is_active' => ['boolean'],
        ];
    }
}
