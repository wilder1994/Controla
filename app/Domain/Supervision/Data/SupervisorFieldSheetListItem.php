<?php

declare(strict_types=1);

namespace App\Domain\Supervision\Data;

use App\Enums\SupervisorFieldSheetKind;
use DateTimeInterface;

final readonly class SupervisorFieldSheetListItem
{
    public function __construct(
        public SupervisorFieldSheetKind $kind,
        public int $id,
        public string $folio,
        public string $typeLabel,
        public string $supervisorName,
        public ?string $clientName,
        public bool $hasNovelty,
        public DateTimeInterface $recordedAt,
    ) {}
}
