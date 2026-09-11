<?php

declare(strict_types=1);

namespace App\Domain\Structure\Data;

final readonly class CreateMemberData
{
    public function __construct(
        public int $clientId,
        public int $structureId,
        public int $memberTypeId,
        public string $firstName,
        public string $lastName,
        public string $documentType,
        public string $documentNumber,
        public string $birthDate,
        public ?string $phonePrimary = null,
        public ?string $phoneSecondary = null,
        public ?string $email = null,
        public bool $hasAppAccess = false,
        public bool $isActive = true,
        public ?string $photoPath = null,
        public ?string $minorTreatmentAcceptedAt = null,
    ) {}
}
