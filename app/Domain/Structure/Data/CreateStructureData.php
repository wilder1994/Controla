<?php

declare(strict_types=1);

namespace App\Domain\Structure\Data;

use App\Enums\StructureType;

final readonly class CreateStructureData
{
    public function __construct(
        public int $clientId,
        public ?int $parentId,
        public string $name,
        public ?string $code,
        public StructureType $type,
        public int $maxOccupancy = 0,
        public bool $isActive = true,
        public ?array $metadata = null,
    ) {}
}
