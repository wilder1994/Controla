<?php

declare(strict_types=1);

namespace App\Domain\Tenant\Data;

use App\Enums\ClientPlanTier;

final readonly class CreateClientData
{
    public function __construct(
        public int $securityCompanyId,
        public string $name,
        public string $slug,
        public string $loginSuffix,
        public ClientPlanTier $planTier,
        public ?string $accessUrl = null,
        public bool $isActive = true,
    ) {}
}
