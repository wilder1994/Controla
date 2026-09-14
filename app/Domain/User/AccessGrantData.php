<?php

declare(strict_types=1);

namespace App\Domain\User;

use App\Enums\AccessGrantLevel;
use App\Enums\AccessGrantScope;

final readonly class AccessGrantData
{
    public function __construct(
        public AccessGrantScope $scope,
        public int $scopeId,
        public string $module,
        public AccessGrantLevel $level,
    ) {}
}
