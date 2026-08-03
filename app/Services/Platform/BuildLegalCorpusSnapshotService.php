<?php

declare(strict_types=1);

namespace App\Services\Platform;

use App\Enums\CompanyPackageSku;
use App\Models\LegalCorpusVersion;
use Illuminate\Support\Collection;

final class BuildLegalCorpusSnapshotService
{
    /**
     * @return list<array{
     *     type: string,
     *     version: string,
     *     title: string,
     *     package_sku: ?string,
     *     content: string,
     *     content_hash: string
     * }>
     */
    public function forPackage(?CompanyPackageSku $sku): array
    {
        return $this->toSnapshot(LegalCorpusVersion::currentForPackage($sku));
    }

    /**
     * @param  Collection<int, LegalCorpusVersion>  $corpus
     * @return list<array{
     *     type: string,
     *     version: string,
     *     title: string,
     *     package_sku: ?string,
     *     content: string,
     *     content_hash: string
     * }>
     */
    public function toSnapshot(Collection $corpus): array
    {
        return $corpus
            ->map(static fn (LegalCorpusVersion $v): array => [
                'type' => $v->type->value,
                'version' => $v->version,
                'title' => $v->title,
                'package_sku' => $v->package_sku,
                'content' => $v->content,
                'content_hash' => $v->content_hash,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $snapshot
     */
    public function hash(array $snapshot, string $representativeName, string $documentNumber): string
    {
        $canonical = json_encode($snapshot, JSON_THROW_ON_ERROR);

        return hash('sha256', $canonical.'|'.$representativeName.'|'.$documentNumber);
    }
}
