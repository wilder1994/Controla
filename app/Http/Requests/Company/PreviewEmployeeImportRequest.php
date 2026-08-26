<?php

declare(strict_types=1);

namespace App\Http\Requests\Company;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;

final class PreviewEmployeeImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Employee::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'file' => ['nullable', 'file', 'mimes:xlsx,xls', 'max:5120'],
            'paste' => ['nullable', 'string', 'max:2000000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $hasFile = $this->file('file') !== null;
            $hasPaste = trim((string) $this->input('paste', '')) !== '';

            if (! $hasFile && ! $hasPaste) {
                $validator->errors()->add('file', 'Arrastra un Excel o pega la tabla.');
            }
        });
    }
}
