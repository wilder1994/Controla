<?php

declare(strict_types=1);

namespace App\Domain\Supervision\Data;

final readonly class SupervisionPeriodSnapshot
{
    /**
     * @param  list<array{name: string, reviews: int, km: int, logs: int}>  $bySupervisor
     * @param  list<array{name: string, reviews: int, novelty: int}>  $byClient
     * @param  array<string, array{label: string, total: int, ok: int, attention: int, critical: int}>  $modules
     * @param  array{total: int, low: int, medium: int, high: int, extreme: int}  $recommendations
     * @param  array<string, mixed>  $charts
     * @param  list<string>  $unvisitedSites
     * @param  list<string>  $alerts
     */
    public function __construct(
        public string $companyName,
        public string $from,
        public string $to,
        public string $caption,
        public int $reviews,
        public int $sitesContracted,
        public int $sitesVisited,
        public int $kmTraveled,
        public int $openShifts,
        public int $fieldLogs,
        public string $semaphore,
        public ?float $coveragePercent,
        public array $bySupervisor,
        public array $byClient,
        public array $modules,
        public array $recommendations,
        public array $charts,
        public array $unvisitedSites,
        public array $alerts,
    ) {}
}
