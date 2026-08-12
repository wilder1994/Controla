<?php

declare(strict_types=1);

namespace App\Services\Platform;

use App\Models\LegalCorpusVersion;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class PublishLegalCorpusVersionService
{
    public function execute(
        LegalCorpusVersion $current,
        string $title,
        string $content,
        ?CarbonImmutable $effectiveFrom = null,
    ): LegalCorpusVersion {
        if (! $current->isCurrent()) {
            throw new InvalidArgumentException('Solo se puede versionar un documento vigente.');
        }

        $title = trim($title);
        $content = trim($content);

        if ($title === '' || $content === '') {
            throw new InvalidArgumentException('Título y contenido son obligatorios.');
        }

        return DB::transaction(function () use ($current, $title, $content, $effectiveFrom): LegalCorpusVersion {
            $now = CarbonImmutable::now();
            $current->update(['superseded_at' => $now]);

            return LegalCorpusVersion::query()->create([
                'type' => $current->type,
                'package_sku' => $current->package_sku,
                'version' => $this->nextVersion($current->version),
                'title' => $title,
                'content' => $content,
                'effective_from' => ($effectiveFrom ?? $now)->toDateString(),
                'superseded_at' => null,
                'content_hash' => hash('sha256', $content),
            ]);
        });
    }

    private function nextVersion(string $current): string
    {
        if (preg_match('/^(\d+)\.(\d+)$/', $current, $matches) === 1) {
            return $matches[1].'.'.(((int) $matches[2]) + 1);
        }

        return $current.'.1';
    }
}
