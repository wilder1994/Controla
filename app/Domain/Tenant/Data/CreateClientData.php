<?php

declare(strict_types=1);

namespace App\Domain\Tenant\Data;

final readonly class CreateClientData
{
    public function __construct(
        public int $securityCompanyId,
        public string $name,
        public string $slug,
        public string $loginSuffix,
        public ?string $address = null,
        public ?string $city = null,
        public ?string $department = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public ?string $accessUrl = null,
        public bool $isActive = true,
    ) {}
}
