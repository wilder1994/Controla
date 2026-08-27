<?php

declare(strict_types=1);

namespace App\Services\Company;

use App\Enums\SupervisorAlarmKind;
use App\Enums\SupervisorAlarmResult;
use App\Enums\SupervisorFieldModule;
use App\Enums\SupervisorRiskLevel;
use App\Models\SupervisorFieldLog;
use App\Models\SupervisorRecommendation;
use App\Models\SupervisorShift;
use App\Models\SupervisorShiftReview;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final class BuildSupervisionPeriodCharts
{
    /**
     * @param  Collection<int, SupervisorShiftReview>  $reviews
     * @param  Collection<int, SupervisorFieldLog>  $logs
     * @param  Collection<int, SupervisorShift>  $shifts
     * @param  Collection<int, SupervisorRecommendation>  $recommendations
     * @return array{
     *   grain: 'day'|'month',
     *   labels: list<string>,
     *   reviews: list<int>,
     *   km: list<int>,
     *   novelty_yes: list<int>,
     *   novelty_no: list<int>,
     *   inventory: array{good: int, regular: int, bad: int},
     *   books: array{yes: int, no: int},
     *   folders: array{complete: int, missing: int},
     *   weapons: array{total: int, cleaned: int, inspection_only: int, novelty: int, expired: int},
     *   recs_by_level: array{low: int, medium: int, high: int, extreme: int},
     *   recs_by_type: list<array{name: string, total: int}>,
     *   alarms_by_type: list<array{name: string, test: int, response: int}>,
     *   alarms_kind: array{test: int, response: int},
     *   alarms_result: array{ok: int, fail: int, real: int, false_alarm: int, not_found: int},
     *   supports_by_type: list<array{name: string, total: int}>,
     *   supports_place: array{site: int, road: int},
     *   documents: array{delivered: int, pending: int}
     * }
     */
    public function execute(
        CarbonImmutable $fromAt,
        CarbonImmutable $toAt,
        Collection $reviews,
        Collection $logs,
        Collection $shifts,
        Collection $recommendations,
    ): array {
        [$grain, $keys, $labels] = $this->buckets($fromAt, $toAt);

        $reviewCounts = array_fill_keys($keys, 0);
        $noveltyYes = array_fill_keys($keys, 0);
        $noveltyNo = array_fill_keys($keys, 0);
        $kmCounts = array_fill_keys($keys, 0);

        foreach ($reviews as $review) {
            $key = $this->keyOf($grain, CarbonImmutable::parse($review->recorded_at));
            if (! array_key_exists($key, $reviewCounts)) {
                continue;
            }
            $reviewCounts[$key]++;
            if ($review->has_novelty) {
                $noveltyYes[$key]++;
            } else {
                $noveltyNo[$key]++;
            }
        }

        foreach ($shifts as $shift) {
            if ($shift->started_at === null) {
                continue;
            }
            $key = $this->keyOf($grain, CarbonImmutable::parse($shift->started_at));
            if (! array_key_exists($key, $kmCounts)) {
                continue;
            }
            $kmCounts[$key] += (int) ($shift->km_traveled ?? 0);
        }

        return [
            'grain' => $grain,
            'labels' => $labels,
            'reviews' => array_values($reviewCounts),
            'km' => array_values($kmCounts),
            'novelty_yes' => array_values($noveltyYes),
            'novelty_no' => array_values($noveltyNo),
            'inventory' => $this->inventoryMix($logs),
            'books' => $this->bookMix($logs),
            'folders' => $this->folderMix($logs),
            'weapons' => $this->weaponMix($logs),
            'recs_by_level' => [
                'low' => $recommendations->where('risk_level', SupervisorRiskLevel::Low)->count(),
                'medium' => $recommendations->where('risk_level', SupervisorRiskLevel::Medium)->count(),
                'high' => $recommendations->where('risk_level', SupervisorRiskLevel::High)->count(),
                'extreme' => $recommendations->where('risk_level', SupervisorRiskLevel::Extreme)->count(),
            ],
            'recs_by_type' => $this->namedCounts($recommendations->map(
                fn (SupervisorRecommendation $row) => (string) ($row->risk_type ?: 'Sin tipo'),
            )),
            'alarms_by_type' => $this->alarmsByType($logs),
            'alarms_kind' => $this->alarmsKind($logs),
            'alarms_result' => $this->alarmsResult($logs),
            'supports_by_type' => $this->namedCounts($logs
                ->filter(fn (SupervisorFieldLog $log) => $log->module === SupervisorFieldModule::Supports)
                ->map(fn (SupervisorFieldLog $log) => (string) (($log->payload['support_type'] ?? null) ?: 'Sin tipo'))),
            'supports_place' => $this->supportsPlace($logs),
            'documents' => $this->documentsMix($logs),
        ];
    }

    /**
     * @return array{0: 'day'|'month', 1: list<string>, 2: list<string>}
     */
    private function buckets(CarbonImmutable $fromAt, CarbonImmutable $toAt): array
    {
        $from = $fromAt->startOfDay();
        $to = $toAt->startOfDay();
        $days = $from->diffInDays($to) + 1;
        $grain = $days > 45 ? 'month' : 'day';
        $keys = [];
        $labels = [];
        $cursor = $grain === 'month' ? $from->startOfMonth() : $from;
        $end = $grain === 'month' ? $to->startOfMonth() : $to;

        while ($cursor->lte($end)) {
            $keys[] = $this->keyOf($grain, $cursor);
            $labels[] = $grain === 'month'
                ? $cursor->format('m/Y')
                : $cursor->format('d/m');
            $cursor = $grain === 'month' ? $cursor->addMonth() : $cursor->addDay();
        }

        return [$grain, $keys, $labels];
    }

    private function keyOf(string $grain, CarbonImmutable $at): string
    {
        return $grain === 'month' ? $at->format('Y-m') : $at->format('Y-m-d');
    }

    /**
     * @param  Collection<int, SupervisorFieldLog>  $logs
     * @return array{good: int, regular: int, bad: int}
     */
    private function inventoryMix(Collection $logs): array
    {
        $mix = ['good' => 0, 'regular' => 0, 'bad' => 0];
        foreach ($logs->where('module', SupervisorFieldModule::Inventory) as $log) {
            foreach ($log->payload['items'] ?? [] as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $status = (string) ($item['status'] ?? '');
                if (array_key_exists($status, $mix)) {
                    $mix[$status]++;
                }
            }
        }

        return $mix;
    }

    /**
     * @param  Collection<int, SupervisorFieldLog>  $logs
     * @return array{yes: int, no: int}
     */
    private function bookMix(Collection $logs): array
    {
        $mix = ['yes' => 0, 'no' => 0];
        foreach ($logs->where('module', SupervisorFieldModule::ControlBooks) as $log) {
            foreach ($log->payload['items'] ?? [] as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $novelty = (string) ($item['novelty'] ?? 'no');
                if (array_key_exists($novelty, $mix)) {
                    $mix[$novelty]++;
                }
            }
        }

        return $mix;
    }

    /**
     * @param  Collection<int, SupervisorFieldLog>  $logs
     * @return array{complete: int, missing: int}
     */
    private function folderMix(Collection $logs): array
    {
        $mix = ['complete' => 0, 'missing' => 0];
        foreach ($logs->where('module', SupervisorFieldModule::Folders) as $log) {
            $status = (string) ($log->payload['status'] ?? 'complete');
            if (array_key_exists($status, $mix)) {
                $mix[$status]++;
            }
        }

        return $mix;
    }

    /**
     * @param  Collection<int, SupervisorFieldLog>  $logs
     * @return array{total: int, cleaned: int, inspection_only: int, novelty: int, expired: int}
     */
    private function weaponMix(Collection $logs): array
    {
        $weapons = $logs->where('module', SupervisorFieldModule::Weapons);
        $cleaned = 0;
        $novelty = 0;
        $expired = 0;
        foreach ($weapons as $log) {
            $payload = $log->payload;
            $didClean = ($payload['cleaned'] ?? null) === 'yes'
                || (is_array($payload['photos'] ?? null) && isset($payload['photos']['cleaning']));
            if ($didClean) {
                $cleaned++;
            }
            if (($payload['novelty'] ?? 'no') === 'yes') {
                $novelty++;
            }
            $expires = (string) ($payload['permit_expires_at'] ?? '');
            if ($expires !== '' && $expires < now()->toDateString()) {
                $expired++;
            }
        }
        $total = $weapons->count();

        return [
            'total' => $total,
            'cleaned' => $cleaned,
            'inspection_only' => max(0, $total - $cleaned),
            'novelty' => $novelty,
            'expired' => $expired,
        ];
    }

    /**
     * @param  Collection<int, SupervisorFieldLog>  $logs
     * @return list<array{name: string, test: int, response: int}>
     */
    private function alarmsByType(Collection $logs): array
    {
        $rows = [];
        foreach ($logs->where('module', SupervisorFieldModule::Alarms) as $log) {
            $name = (string) (($log->payload['alarm_type'] ?? null) ?: 'Sin tipo');
            $kind = (string) ($log->payload['kind'] ?? SupervisorAlarmKind::Test->value);
            if (! isset($rows[$name])) {
                $rows[$name] = ['name' => $name, 'test' => 0, 'response' => 0];
            }
            if ($kind === SupervisorAlarmKind::Response->value) {
                $rows[$name]['response']++;
            } else {
                $rows[$name]['test']++;
            }
        }
        usort($rows, fn (array $a, array $b) => ($b['test'] + $b['response']) <=> ($a['test'] + $a['response']));

        return array_values($rows);
    }

    /**
     * @param  Collection<int, SupervisorFieldLog>  $logs
     * @return array{test: int, response: int}
     */
    private function alarmsKind(Collection $logs): array
    {
        $mix = ['test' => 0, 'response' => 0];
        foreach ($logs->where('module', SupervisorFieldModule::Alarms) as $log) {
            $kind = (string) ($log->payload['kind'] ?? SupervisorAlarmKind::Test->value);
            if (array_key_exists($kind, $mix)) {
                $mix[$kind]++;
            }
        }

        return $mix;
    }

    /**
     * @param  Collection<int, SupervisorFieldLog>  $logs
     * @return array{ok: int, fail: int, real: int, false_alarm: int, not_found: int}
     */
    private function alarmsResult(Collection $logs): array
    {
        $mix = [
            'ok' => 0,
            'fail' => 0,
            'real' => 0,
            'false_alarm' => 0,
            'not_found' => 0,
        ];
        foreach ($logs->where('module', SupervisorFieldModule::Alarms) as $log) {
            $result = (string) ($log->payload['result'] ?? SupervisorAlarmResult::Ok->value);
            if (array_key_exists($result, $mix)) {
                $mix[$result]++;
            }
        }

        return $mix;
    }

    /**
     * @param  Collection<int, SupervisorFieldLog>  $logs
     * @return array{site: int, road: int}
     */
    private function supportsPlace(Collection $logs): array
    {
        $supports = $logs->where('module', SupervisorFieldModule::Supports);
        $site = $supports->filter(fn (SupervisorFieldLog $log) => $log->client_id !== null)->count();

        return [
            'site' => $site,
            'road' => $supports->count() - $site,
        ];
    }

    /**
     * @param  Collection<int, SupervisorFieldLog>  $logs
     * @return array{delivered: int, pending: int}
     */
    private function documentsMix(Collection $logs): array
    {
        $mix = ['delivered' => 0, 'pending' => 0];
        foreach ($logs->where('module', SupervisorFieldModule::Documents) as $log) {
            foreach ($log->payload['items'] ?? [] as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $mix['delivered'] += (int) ($item['delivered'] ?? 0);
                $mix['pending'] += (int) ($item['pending'] ?? 0);
            }
        }

        return $mix;
    }

    /**
     * @param  Collection<int, string>  $names
     * @return list<array{name: string, total: int}>
     */
    private function namedCounts(Collection $names): array
    {
        $rows = [];
        foreach ($names as $name) {
            $label = $name !== '' ? $name : 'Sin tipo';
            $rows[$label] = ($rows[$label] ?? 0) + 1;
        }
        arsort($rows);
        $out = [];
        foreach ($rows as $name => $total) {
            $out[] = ['name' => $name, 'total' => $total];
        }

        return $out;
    }
}
