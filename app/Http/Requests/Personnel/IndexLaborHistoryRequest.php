<?php

declare(strict_types=1);

namespace App\Http\Requests\Personnel;

use App\Enums\DocumentFolder;
use App\Enums\OtherDocumentType;
use App\Models\EmployeeDocumentBatch;
use App\Support\Personnel\IndexedFolder;
use App\Support\Personnel\OtherSupportNamer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use InvalidArgumentException;

final class IndexLaborHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('company.settings.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'slices' => ['required', 'array', 'min:1'],
            'slices.*.folder' => ['required', Rule::enum(DocumentFolder::class)],
            'slices.*.document_type' => ['required', 'string'],
            'slices.*.display_name' => ['required', 'string', 'max:180'],
            'slices.*.tipo' => ['nullable', 'string', 'max:80'],
            'slices.*.pages' => ['required', 'array', 'min:1'],
            'slices.*.pages.*' => ['required', 'integer', 'min:1'],
            'slices.*.taken_on' => ['nullable', 'date'],
            'slices.*.provider' => ['nullable', 'string', 'max:180'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator): void {
            $routeBatch = $this->route('batch');
            $batchId = $routeBatch instanceof EmployeeDocumentBatch ? $routeBatch->id : (int) $routeBatch;
            $batch = EmployeeDocumentBatch::query()->with('employee.documents')->find($batchId);
            $employee = $batch?->employee;
            $seenTipos = [];
            $otrosIncoming = 0;

            foreach ($this->input('slices', []) as $index => $slice) {
                $pages = array_map('intval', $slice['pages'] ?? []);
                if ($pages !== array_values(array_unique($pages))) {
                    $validator->errors()->add('slices.'.$index.'.pages', 'Hay páginas repetidas en este corte.');
                }

                $folder = DocumentFolder::tryFrom((string) ($slice['folder'] ?? ''));
                if ($folder === null) {
                    continue;
                }

                if (isset($slice['document_type'])) {
                    try {
                        IndexedFolder::resolve($folder, (string) $slice['document_type']);
                    } catch (InvalidArgumentException) {
                        $validator->errors()->add('slices.'.$index.'.document_type', 'Tipo no válido para esta carpeta.');
                    }
                }

                if ($folder === DocumentFolder::Cursos) {
                    if (blank($slice['taken_on'] ?? null)) {
                        $validator->errors()->add('slices.'.$index.'.taken_on', 'La fecha del curso es obligatoria.');
                    }
                    if (blank($slice['provider'] ?? null)) {
                        $validator->errors()->add('slices.'.$index.'.provider', 'La entidad que dicta el curso es obligatoria.');
                    }
                }

                if ($folder !== DocumentFolder::Otros || $employee === null) {
                    continue;
                }

                $otrosIncoming++;
                $tipo = trim((string) ($slice['tipo'] ?? ''));
                if ($tipo === '') {
                    $validator->errors()->add('slices.'.$index.'.tipo', 'Digite el tipo del soporte.');

                    continue;
                }

                $tipoKey = OtherSupportNamer::normalize($tipo);
                if ($tipoKey !== '' && isset($seenTipos[$tipoKey])) {
                    $validator->errors()->add('slices.'.$index.'.tipo', 'Ese tipo ya está en la lista. Cámbielo para no repetirlo.');
                }
                $seenTipos[$tipoKey] = true;

                $name = (string) ($slice['display_name'] ?? '');
                $conflict = OtherSupportNamer::conflict($tipo, $name, $employee);
                if ($conflict !== null) {
                    $validator->errors()->add(
                        'slices.'.$index.'.tipo',
                        'Ese tipo o nombre coincide con '.$conflict['folder'].' ('.$conflict['label'].'). Cámbielo o cárguelo en esa carpeta.',
                    );
                }

                foreach ($employee->documents as $existing) {
                    if ($existing->folder !== DocumentFolder::Otros || ! $existing->hasFile()) {
                        continue;
                    }
                    if (OtherSupportNamer::normalize((string) $existing->display_name) === $tipoKey) {
                        $validator->errors()->add('slices.'.$index.'.tipo', 'Ya hay un soporte con ese tipo. Cámbielo o use otro nombre.');
                    }
                }
            }

            if ($employee !== null && OtherSupportNamer::loadedCount($employee) + $otrosIncoming > OtherDocumentType::MAX) {
                $validator->errors()->add('slices', 'Solo se permiten '.OtherDocumentType::MAX.' soportes en Otros por trabajador.');
            }
        });
    }
}
