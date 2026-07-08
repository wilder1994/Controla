<?php

declare(strict_types=1);

namespace App\Domain\Structure\Data;

use App\Enums\MemberType;

final readonly class CreateMemberData
{
    public function __construct(
        public int $clientId,
        public int $structureId,
        public string $firstName,
        public string $lastName,
        public string $documentNumber,
        public MemberType $memberType,
        public ?string $phonePrimary = null,
        public ?string $phoneSecondary = null,
        public ?string $email = null,
        public bool $hasAppAccess = false,
        public bool $isActive = true,
    ) {}
}
