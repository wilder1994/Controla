<?php

declare(strict_types=1);

namespace App\Services\Company;

use App\Models\CompanyCollaboratorType;
use Illuminate\Validation\ValidationException;

final class ManageCompanyCollaboratorTypeService
{
    /** @param array{name: string, is_active?: bool} $data */
    public function create(int $companyId, array $data): CompanyCollaboratorType
    {
        $name = trim($data['name']);
        $this->assertUniqueName($companyId, $name);

        $max = (int) CompanyCollaboratorType::query()
            ->where('security_company_id', $companyId)
            ->max('sort_order');

        return CompanyCollaboratorType::query()->create([
            'security_company_id' => $companyId,
            'name' => $name,
            'is_active' => (bool) ($data['is_active'] ?? true),
            'sort_order' => $max + 10,
        ]);
    }

    /** @param array{name?: string, is_active?: bool} $data */
    public function update(CompanyCollaboratorType $type, array $data): CompanyCollaboratorType
    {
        if (isset($data['name'])) {
            $name = trim((string) $data['name']);
            $this->assertUniqueName((int) $type->security_company_id, $name, $type->id);
            $type->name = $name;
        }

        if (array_key_exists('is_active', $data)) {
            $type->is_active = (bool) $data['is_active'];
        }

        $type->save();

        return $type->refresh();
    }

    public function findOrCreate(int $companyId, string $name): CompanyCollaboratorType
    {
        $name = trim($name);
        $existing = CompanyCollaboratorType::query()
            ->where('security_company_id', $companyId)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return $this->create($companyId, ['name' => $name, 'is_active' => true]);
    }

    public function delete(CompanyCollaboratorType $type): void
    {
        if ($type->employees()->exists()) {
            throw ValidationException::withMessages([
                'collaborator_type' => 'No se puede eliminar: hay empleados usando este tipo.',
            ]);
        }

        $type->delete();
    }

    private function assertUniqueName(int $companyId, string $name, ?int $ignoreId = null): void
    {
        $exists = CompanyCollaboratorType::query()
            ->where('security_company_id', $companyId)
            ->where('name', $name)
            ->when($ignoreId !== null, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'name' => 'Ya existe un tipo de colaborador con ese nombre en la empresa.',
            ]);
        }
    }
}
