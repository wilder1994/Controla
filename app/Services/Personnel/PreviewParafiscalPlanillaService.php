<?php

declare(strict_types=1);

namespace App\Services\Personnel;

use App\Models\Employee;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class PreviewParafiscalPlanillaService
{
    public const CACHE_TTL_SECONDS = 1800;

    public function __construct(
        private readonly ParsePilaPlanillaService $parser,
    ) {}

    public function cacheKey(int $companyId, int $userId): string
    {
        return "parafiscal-planilla.{$companyId}.{$userId}";
    }

    /** @return array<string, mixed> */
    public function previewFile(UploadedFile $file, int $companyId, int $userId): array
    {
        $this->forget($companyId, $userId);

        $originalName = $file->getClientOriginalName() ?: 'planilla.xlsx';
        $relative = sprintf('tmp/parafiscales/%d/%d/%s.xlsx', $companyId, $userId, Str::uuid()->toString());
        $absolute = storage_path('app/'.$relative);
        File::ensureDirectoryExists(dirname($absolute));
        $file->move(dirname($absolute), basename($absolute));

        try {
            $parsed = $this->parser->parsePath($absolute);
        } catch (InvalidArgumentException $e) {
            File::delete($absolute);
            throw $e;
        }

        $employees = Employee::query()
            ->where('security_company_id', $companyId)
            ->get(['id', 'document_number', 'first_names', 'last_name_paternal', 'last_name_maternal']);

        $byDocument = [];
        foreach ($employees as $employee) {
            $key = ParsePilaPlanillaService::normalizeCedula((string) $employee->document_number);
            if ($key !== '') {
                $byDocument[$key] = $employee;
            }
        }

        $matched = [];
        $skipped = [];
        foreach ($parsed['groups'] as $document => $group) {
            $employee = $byDocument[$document] ?? null;
            $row = [
                'document' => $group['document'],
                'name' => $group['name'] !== '' ? $group['name'] : ($employee?->fullName() ?? ''),
                'rows_count' => count($group['rows']),
                'total' => $group['total'],
            ];

            if ($employee === null) {
                $skipped[] = $row;

                continue;
            }

            $row['employee_id'] = $employee->id;
            $row['employee_name'] = $employee->fullName();
            $matched[] = $row;
        }

        return [
            'company_id' => $companyId,
            'path' => $relative,
            'original_name' => $originalName,
            'pension_period' => $parsed['pension_period'],
            'salud_period' => $parsed['salud_period'],
            'matched' => $matched,
            'skipped' => $skipped,
        ];
    }

    /** @param array<string, mixed> $preview */
    public function put(int $companyId, int $userId, array $preview): void
    {
        Cache::put($this->cacheKey($companyId, $userId), $preview, self::CACHE_TTL_SECONDS);
    }

    /** @return array<string, mixed>|null */
    public function get(int $companyId, int $userId): ?array
    {
        $preview = Cache::get($this->cacheKey($companyId, $userId));

        return is_array($preview) ? $preview : null;
    }

    public function forget(int $companyId, int $userId): void
    {
        $preview = $this->get($companyId, $userId);
        if (is_array($preview) && isset($preview['path']) && is_string($preview['path'])) {
            $absolute = storage_path('app/'.$preview['path']);
            if (is_file($absolute)) {
                File::delete($absolute);
            }
        }

        Cache::forget($this->cacheKey($companyId, $userId));
    }
}
