<?php

declare(strict_types=1);

namespace App\Domain\Supervision\Data;

use Illuminate\Http\UploadedFile;

final readonly class OpenSupervisorShiftInput
{
    /**
     * @param  array<string, bool>  $ppeChecklist
     * @param  array<string, bool>  $vehicleChecklist
     */
    public function __construct(
        public int $shiftTemplateId,
        public int $zoneId,
        public int $kmStart,
        public array $ppeChecklist,
        public array $vehicleChecklist,
        public UploadedFile $odometerPhoto,
        public UploadedFile $selfiePhoto,
        public ?int $vehicleId,
        public ?string $plate,
        public ?string $brand,
        public ?string $line,
        public ?string $model,
        public ?string $color,
        public ?string $type,
        public ?string $soatExpiresAt,
        public ?string $technicalReviewExpiresAt,
    ) {}
}
