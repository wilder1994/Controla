<?php

declare(strict_types=1);

namespace App\Domain\Supervision\Data;

use Illuminate\Http\UploadedFile;

final readonly class RecordSupervisorShiftReviewInput
{
    public function __construct(
        public int $clientId,
        public int $supervisorPostId,
        public int $employeeId,
        public string $notes,
        public bool $hasNovelty,
        public UploadedFile $guardPhoto,
        public float $latitude,
        public float $longitude,
    ) {}
}
