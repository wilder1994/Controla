<?php

declare(strict_types=1);

namespace App\Services\Platform;

use App\Models\LifecycleEvidenceEvent;
use App\Models\PlatformDocument;
use App\Models\SecurityCompany;
use App\Models\SubscriptionAcceptance;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final class PlatformDocumentsHubService
{
    /** @return array<string, int|string> */
    public function hubKpis(): array
    {
        $monthStart = CarbonImmutable::now()->startOfMonth();

        return [
            'expedientes_total' => SecurityCompany::query()->count(),
            'acceptances_pending' => SecurityCompany::query()
                ->whereDoesntHave('subscriptionAcceptances')
                ->count(),
            'demo_invoices_month' => PlatformDocument::query()
                ->where('is_demo', true)
                ->where('issued_at', '>=', $monthStart)
                ->count(),
            'evidence_events_30d' => LifecycleEvidenceEvent::query()
                ->where('occurred_at', '>=', CarbonImmutable::now()->subDays(30))
                ->count(),
            'billing_mode' => config('billing.mode', 'demo'),
        ];
    }

    /** @return Collection<int, SecurityCompany> */
    public function expedientesListing(): Collection
    {
        return SecurityCompany::query()
            ->withCount(['subscriptionAcceptances', 'platformDocuments', 'lifecycleEvidenceEvents'])
            ->orderBy('legal_name')
            ->get();
    }

    /** @return array{timeline: Collection, documents: Collection, acceptance: ?SubscriptionAcceptance, payments: Collection} */
    public function expedienteDetail(SecurityCompany $company): array
    {
        $company->load([
            'subscriptionAcceptances' => fn ($q) => $q->latest('accepted_at'),
            'platformDocuments' => fn ($q) => $q->latest('issued_at'),
            'commercialPayments' => fn ($q) => $q->latest('paid_at'),
            'lifecycleEvidenceEvents' => fn ($q) => $q->latest('occurred_at'),
        ]);

        $timeline = $company->lifecycleEvidenceEvents;

        return [
            'timeline' => $timeline,
            'documents' => $company->platformDocuments,
            'acceptance' => $company->subscriptionAcceptances->first(),
            'payments' => $company->commercialPayments,
        ];
    }
}
