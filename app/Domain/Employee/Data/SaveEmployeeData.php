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
        public ?string $education = null,
        public ?string $maritalStatus = null,
        public ?int $childrenCount = null,
        public ?string $phone = null,
        public ?string $residenceCity = null,
        public ?string $address = null,
        public ?string $engagementType = null,
        public ?string $contributorType = null,
        public ?string $laborContractType = null,
        public ?string $hiredOn = null,
        public ?string $laborContractEndsOn = null,
        public ?string $leftOn = null,
        public ?string $epsCode = null,
        public ?string $epsName = null,
        public ?string $afpCode = null,
        public ?string $afpName = null,
        public ?string $compensationFund = null,
        public ?string $arlName = null,
        public ?string $arlRiskLevel = null,
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
            education: self::nullableString($validated['education'] ?? null),
            maritalStatus: self::nullableString($validated['marital_status'] ?? null),
            childrenCount: self::nullableInt($validated['children_count'] ?? null),
            phone: self::nullableString($validated['phone'] ?? null),
            residenceCity: self::nullableString($validated['residence_city'] ?? null),
            address: self::nullableString($validated['address'] ?? null),
            engagementType: self::nullableString($validated['engagement_type'] ?? null),
            contributorType: self::nullableString($validated['contributor_type'] ?? null),
            laborContractType: self::nullableString($validated['labor_contract_type'] ?? null),
            hiredOn: self::nullableString($validated['hired_on'] ?? null),
            laborContractEndsOn: self::nullableString($validated['labor_contract_ends_on'] ?? null),
            leftOn: self::nullableString($validated['left_on'] ?? null),
            epsCode: self::nullableString($validated['eps_code'] ?? null),
            epsName: self::nullableString($validated['eps_name'] ?? null),
            afpCode: self::nullableString($validated['afp_code'] ?? null),
            afpName: self::nullableString($validated['afp_name'] ?? null),
            compensationFund: self::nullableString($validated['compensation_fund'] ?? null),
            arlName: self::nullableString($validated['arl_name'] ?? null),
            arlRiskLevel: self::nullableString($validated['arl_risk_level'] ?? null),
        );
    }

    private static function nullableString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }

    private static function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
