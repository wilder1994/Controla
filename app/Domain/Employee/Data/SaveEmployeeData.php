<?php

declare(strict_types=1);

namespace App\Domain\Employee\Data;

use App\Enums\BloodGroup;
use App\Enums\Sex;

final readonly class SaveEmployeeData
{
    public function __construct(
        public int $securityCompanyId,
        public int $jobTitleId,
        public string $documentType,
        public string $documentNumber,
        public string $lastNamePaternal,
        public string $lastNameMaternal,
        public string $firstNames,
        public Sex $sex,
        public string $birthDate,
        public int $collaboratorTypeId,
        public string $email,
        public string $nationality,
        public BloodGroup $bloodGroup,
        public ?string $birthDepartment = null,
        public ?string $birthCity = null,
        public ?string $emergencyPhone = null,
        public ?string $emergencyContact = null,
        public bool $hasDisability = false,
        public ?string $documentIssueDepartment = null,
        public ?string $documentIssueCity = null,
        public ?string $documentIssuedAt = null,
        public ?bool $sameCostCenter = null,
    ) {}

    /** @param array<string, mixed> $validated */
    public static function fromValidated(array $validated, int $companyId): self
    {
        $sameCostCenter = $validated['same_cost_center'] ?? null;

        return new self(
            securityCompanyId: $companyId,
            jobTitleId: (int) $validated['job_title_id'],
            documentType: (string) $validated['document_type'],
            documentNumber: (string) $validated['document_number'],
            lastNamePaternal: trim((string) ($validated['last_name_paternal'] ?? '')),
            lastNameMaternal: trim((string) ($validated['last_name_maternal'] ?? '')),
            firstNames: (string) $validated['first_names'],
            sex: Sex::from((string) $validated['sex']),
            birthDate: (string) $validated['birth_date'],
            collaboratorTypeId: (int) $validated['collaborator_type_id'],
            email: (string) $validated['email'],
            nationality: (string) $validated['nationality'],
            bloodGroup: BloodGroup::from((string) $validated['blood_group']),
            birthDepartment: self::nullableString($validated['birth_department'] ?? null),
            birthCity: self::nullableString($validated['birth_city'] ?? null),
            emergencyPhone: self::nullableString($validated['emergency_phone'] ?? null),
            emergencyContact: self::nullableString($validated['emergency_contact'] ?? null),
            hasDisability: (bool) ($validated['has_disability'] ?? false),
            documentIssueDepartment: self::nullableString($validated['document_issue_department'] ?? null),
            documentIssueCity: self::nullableString($validated['document_issue_city'] ?? null),
            documentIssuedAt: self::nullableString($validated['document_issued_at'] ?? null),
            sameCostCenter: $sameCostCenter === null ? null : (bool) $sameCostCenter,
        );
    }

    private static function nullableString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }
}
