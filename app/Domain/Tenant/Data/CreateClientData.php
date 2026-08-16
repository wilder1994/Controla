<?php

declare(strict_types=1);

namespace App\Domain\Tenant\Data;

use App\Enums\PartyType;

final readonly class CreateClientData
{
    public function __construct(
        public int $securityCompanyId,
        public string $name,
        public PartyType $partyType,
        public ?string $legalName = null,
        public ?string $documentType = null,
        public ?string $taxId = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $representativeName = null,
        public ?string $representativeEmail = null,
        public int $structureTypeId,
        public ?string $address = null,
        public ?string $city = null,
        public ?string $department = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public bool $isActive = true,
        public ?string $serviceStartedAt = null,
    ) {}
}
