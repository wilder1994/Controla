<?php

declare(strict_types=1);

namespace App\Services\Company;

use App\Models\SupervisorShift;
use App\Models\SupervisorShiftTemplate;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

final class ResolveSupervisorShiftDeadlineService
{
    public const GRACE_MINUTES = 30;

    public const FALLBACK_IDLE_HOURS = 3;

    public function graceDeadline(SupervisorShift $shift, ?CarbonInterface $now = null): ?CarbonImmutable
    {
        $now = CarbonImmutable::parse(($now ?? CarbonImmutable::now())->toIso8601String());
        $opened = $shift->started_at;
        if ($opened === null) {
            return null;
        }
        $openedAt = CarbonImmutable::parse($opened->toIso8601String());

        $template = $shift->shiftTemplate;
        if ($template instanceof SupervisorShiftTemplate && $template->starts_at && $template->ends_at) {
            $end = $this->scheduledEnd($openedAt, (string) $template->starts_at, (string) $template->ends_at);

            return $end->addMinutes(self::GRACE_MINUTES);
        }

        $lastGps = $shift->locations()->orderByDesc('recorded_at')->value('recorded_at');
        $anchor = $lastGps !== null
            ? CarbonImmutable::parse((string) $lastGps)
            : $openedAt;

        return $anchor->addHours(self::FALLBACK_IDLE_HOURS);
    }

    public function scheduledEnd(CarbonImmutable $openedAt, string $startsAt, string $endsAt): CarbonImmutable
    {
        $tz = (string) config('app.timezone');
        $opened = $openedAt->timezone($tz);
        [$startH, $startM] = $this->clock($startsAt);
        [$endH, $endM] = $this->clock($endsAt);
        $overnight = ($endH * 60 + $endM) <= ($startH * 60 + $startM);

        if ($overnight) {
            $startToday = $opened->setTime($startH, $startM, 0);
            if ($opened->gte($startToday)) {
                return $opened->addDay()->setTime($endH, $endM, 0);
            }

            return $opened->setTime($endH, $endM, 0);
        }

        return $opened->setTime($endH, $endM, 0);
    }

    /** @return array{0: int, 1: int} */
    private function clock(string $value): array
    {
        $parts = explode(':', $value);

        return [(int) ($parts[0] ?? 0), (int) ($parts[1] ?? 0)];
    }
}
