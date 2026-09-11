<?php

declare(strict_types=1);

namespace App\Domain\Supervision\Data;

use App\Enums\SupervisorFieldSheetKind;
use DateTimeInterface;

final readonly class SupervisorFieldSheet
{
    /**
     * @param  list<array{title: string, rows: list<string>, photos: list<array{label: string, src: string}>}>  $sections
     */
    public function __construct(
        public SupervisorFieldSheetKind $kind,
        public int $id,
        public string $folio,
        public string $companyName,
        public string $companyLegalName,
        public ?string $companyTaxId,
        public ?string $companyLogoSrc,
        public string $intro,
        public string $supervisorName,
        public ?string $username,
        public ?string $zoneName,
        public ?string $shiftLabel,
        public DateTimeInterface $recordedAt,
        public ?string $clientName,
        public ?string $installationName,
        public ?string $postName,
        public ?string $guardName,
        public bool $hasNovelty,
        public ?string $notes,
        public ?float $latitude,
        public ?float $longitude,
        public ?string $guardPhotoSrc,
        public array $sections,
    ) {}
}
