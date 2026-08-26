<?php

declare(strict_types=1);

namespace App\Domain\Supervision\Data;

use App\Enums\SupervisorFieldOutcome;

final readonly class ValidatedFieldPayload
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public array $payload,
        public SupervisorFieldOutcome $outcome,
    ) {}
}
