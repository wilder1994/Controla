<?php

declare(strict_types=1);

namespace App\Domain\User;

final readonly class UpdateUserData
{
    /** @param list<int>|null $clientIds */
    public function __construct(
        public string $name,
        public string $email,
        public ?string $password,
        public ?string $role,
        public ?array $clientIds,
        public bool $isActive,
    ) {}
}
