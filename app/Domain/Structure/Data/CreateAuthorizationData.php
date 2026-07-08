<?php

declare(strict_types=1);

namespace App\Domain\Structure\Data;

use App\Enums\VisitorCategory;

final readonly class CreateAuthorizationData
{
    public function __construct(
        public int $clientId,
        public int $structureId,
        public ?int $memberId,
        public string $visitorName,
        public ?string $visitorDocument,
        public VisitorCategory $visitorCategory,
        public string $validForDate,
        public ?string $notes = null,
    ) {}
}
