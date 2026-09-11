<?php

declare(strict_types=1);

namespace App\Http\Requests\Client;

use App\Models\MemberType;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateMemberTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $type = $this->route('memberType');

        return $type instanceof MemberType
            && ($this->user()?->can('update', $type) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['name' => 'nombre'];
    }
}
