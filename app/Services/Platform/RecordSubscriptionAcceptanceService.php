<?php

declare(strict_types=1);

namespace App\Services\Platform;

use App\Enums\EvidenceEventType;
use App\Models\LegalCorpusVersion;
use App\Models\PlatformDocument;
use App\Models\SecurityCompany;
use App\Models\SubscriptionAcceptance;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class RecordSubscriptionAcceptanceService
{
    public function __construct(
        private readonly RecordLifecycleEvidenceService $evidenceService,
    ) {}

    public function execute(
        SecurityCompany $company,
        User $user,
        string $representativeName,
        string $representativeRole,
        string $documentType,
        string $documentNumber,
        Request $request,
    ): SubscriptionAcceptance {
        return DB::transaction(function () use ($company, $user, $representativeName, $representativeRole, $documentType, $documentNumber, $request) {
            $corpus = LegalCorpusVersion::currentForAllTypes();
            $snapshot = $corpus->map(fn (LegalCorpusVersion $v) => [
                'type' => $v->type->value,
                'version' => $v->version,
                'title' => $v->title,
                'content_hash' => $v->content_hash,
            ])->values()->all();

            $canonical = json_encode($snapshot, JSON_THROW_ON_ERROR);
            $contentHash = hash('sha256', $canonical.'|'.$representativeName.'|'.$documentNumber);
            $acceptedAt = CarbonImmutable::now();

            $acceptance = SubscriptionAcceptance::query()->create([
                'security_company_id' => $company->id,
                'user_id' => $user->id,
                'representative_name' => $representativeName,
                'representative_role' => $representativeRole,
                'representative_document_type' => $documentType,
                'representative_document_number' => $documentNumber,
                'corpus_snapshot' => $snapshot,
                'content_hash' => $contentHash,
                'ip_address' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 500),
                'accepted_at' => $acceptedAt,
            ]);

            PlatformDocument::query()->create([
                'security_company_id' => $company->id,
                'type' => \App\Enums\PlatformDocumentType::Contract,
                'title' => 'Paquete contractual aceptado',
                'reference_number' => 'ACC-'.$acceptance->id,
                'metadata' => [
                    'acceptance_id' => $acceptance->id,
                    'corpus_snapshot' => $snapshot,
                ],
                'issued_at' => $acceptedAt,
                'retention_until' => $acceptedAt->addYears(10)->toDateString(),
                'created_by_user_id' => $user->id,
                'is_demo' => config('billing.mode') === 'demo',
            ]);

            $this->evidenceService->record(
                EvidenceEventType::SubscriptionAccepted,
                'Aceptación contractual registrada',
                [
                    'acceptance_id' => $acceptance->id,
                    'representative_name' => $representativeName,
                    'representative_document_number' => $documentNumber,
                    'content_hash' => $contentHash,
                ],
                $company->id,
            );

            return $acceptance;
        });
    }
}
