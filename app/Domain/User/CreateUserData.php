<?php

declare(strict_types=1);

namespace App\Domain\User;

final readonly class CreateUserData
{
    /** @param list<int> $clientIds */
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public string $role,
        public ?int $securityCompanyId,
        public array $clientIds = [],
        public bool $isActive = true,
        public ?string $jobTitle = null,
        public ?string $avatarPath = null,
    ) {}
}
