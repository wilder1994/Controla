<?php

declare(strict_types=1);

namespace App\Services\Company;

use App\Models\CompanyCollaboratorType;
use App\Models\CompanyJobTitle;
use Illuminate\Validation\ValidationException;

final class CreateEmployeeCatalogItemsService
{
    public function __construct(
        private readonly ManageCompanyCollaboratorTypeService $manageCompanyCollaboratorTypeService,
        private readonly ManageCompanyJobTitleService $manageCompanyJobTitleService,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     * @return array{collaborator_type: ?CompanyCollaboratorType, job_title: ?CompanyJobTitle}
     */
    public function execute(int $companyId, array $input): array
    {
        $needsType = ! $this->hasTypes($companyId);
        $needsTitle = ! $this->hasTitles($companyId);

        if (! $needsType && ! $needsTitle) {
            throw ValidationException::withMessages([
                'collaborator_type_name' => 'Esta empresa ya tiene tipo y cargo. Elígelos en el formulario.',
            ]);
        }

        $typeName = trim((string) ($input['collaborator_type_name'] ?? ''));
        $titleName = trim((string) ($input['job_title_name'] ?? ''));
        $errors = [];

        if ($needsType && $typeName === '') {
            $errors['collaborator_type_name'] = 'Indica el primer tipo de colaborador.';
        }
        if ($needsTitle && $titleName === '') {
            $errors['job_title_name'] = 'Indica el primer cargo.';
        }
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return [
            'collaborator_type' => $needsType
                ? $this->manageCompanyCollaboratorTypeService->create($companyId, ['name' => $typeName, 'is_active' => true])
                : null,
            'job_title' => $needsTitle
                ? $this->manageCompanyJobTitleService->create($companyId, ['name' => $titleName, 'is_active' => true])
                : null,
        ];
    }

    private function hasTypes(int $companyId): bool
    {
        return CompanyCollaboratorType::query()
            ->where('security_company_id', $companyId)
            ->where('is_active', true)
            ->exists();
    }

    private function hasTitles(int $companyId): bool
    {
        return CompanyJobTitle::query()
            ->where('security_company_id', $companyId)
            ->where('is_active', true)
            ->exists();
    }
}
