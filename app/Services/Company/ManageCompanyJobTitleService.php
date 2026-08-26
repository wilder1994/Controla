<?php

declare(strict_types=1);

namespace App\Services\Company;

use App\Models\CompanyJobTitle;
use Illuminate\Validation\ValidationException;

final class ManageCompanyJobTitleService
{
    /** @param array{name: string, is_active?: bool} $data */
    public function create(int $companyId, array $data): CompanyJobTitle
    {
        $name = trim($data['name']);
        $this->assertUniqueName($companyId, $name);

        $max = (int) CompanyJobTitle::query()
            ->where('security_company_id', $companyId)
            ->max('sort_order');

        return CompanyJobTitle::query()->create([
            'security_company_id' => $companyId,
            'name' => $name,
            'is_active' => (bool) ($data['is_active'] ?? true),
            'sort_order' => $max + 10,
        ]);
    }

    /** @param array{name?: string, is_active?: bool} $data */
    public function update(CompanyJobTitle $jobTitle, array $data): CompanyJobTitle
    {
        if (isset($data['name'])) {
            $name = trim((string) $data['name']);
            $this->assertUniqueName((int) $jobTitle->security_company_id, $name, $jobTitle->id);
            $jobTitle->name = $name;
        }

        if (array_key_exists('is_active', $data)) {
            $jobTitle->is_active = (bool) $data['is_active'];
        }

        $jobTitle->save();

        return $jobTitle->refresh();
    }

    public function findOrCreate(int $companyId, string $name): CompanyJobTitle
    {
        $name = trim($name);
        $existing = CompanyJobTitle::query()
            ->where('security_company_id', $companyId)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return $this->create($companyId, ['name' => $name, 'is_active' => true]);
    }

    public function delete(CompanyJobTitle $jobTitle): void
    {
        if ($jobTitle->employees()->exists()) {
            throw ValidationException::withMessages([
                'job_title' => 'No se puede eliminar: hay empleados usando este cargo.',
            ]);
        }

        $jobTitle->delete();
    }

    private function assertUniqueName(int $companyId, string $name, ?int $ignoreId = null): void
    {
        $exists = CompanyJobTitle::query()
            ->where('security_company_id', $companyId)
            ->where('name', $name)
            ->when($ignoreId !== null, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'name' => 'Ya existe un cargo con ese nombre en la empresa.',
            ]);
        }
    }
}
