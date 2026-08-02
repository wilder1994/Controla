<?php

declare(strict_types=1);

namespace App\Services\Platform;

use App\Enums\EvidenceEventType;
use App\Enums\PlatformDocumentType;
use App\Models\CommercialPayment;
use App\Models\PlatformDocument;
use App\Models\SecurityCompany;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final class IssueDemoInvoiceService
{
    public function __construct(
        private readonly RecordLifecycleEvidenceService $evidenceService,
    ) {}

    public function execute(
        SecurityCompany $company,
        User $issuedBy,
        ?CommercialPayment $payment = null,
    ): PlatformDocument {
        $now = CarbonImmutable::now();
        $prefix = config('billing.demo_invoice_prefix', 'DEMO');
        $sequence = PlatformDocument::query()
            ->where('type', PlatformDocumentType::Invoice)
            ->where('is_demo', true)
            ->count() + 1;
        $reference = sprintf('%s-%s-%04d', $prefix, $now->format('Y'), $sequence);
        $amount = $payment?->amount ?? $company->contractedAmount();
        $isDemo = config('billing.mode') !== 'live';

        $document = PlatformDocument::query()->create([
            'security_company_id' => $company->id,
            'type' => PlatformDocumentType::Invoice,
            'title' => $isDemo ? 'Factura electrónica (demo)' : 'Factura electrónica',
            'reference_number' => $reference,
            'amount' => $amount,
            'is_demo' => $isDemo,
            'cufe' => $isDemo ? null : null,
            'metadata' => [
                'payment_id' => $payment?->id,
                'billing_mode' => config('billing.mode'),
                'party_type' => $company->party_type?->value,
            ],
            'issued_at' => $now,
            'retention_until' => $now->addYears(10)->toDateString(),
            'created_by_user_id' => $issuedBy->id,
        ]);

        $this->evidenceService->record(
            EvidenceEventType::InvoiceIssued,
            $isDemo ? 'Factura demo emitida' : 'Factura emitida',
            [
                'document_id' => $document->id,
                'reference_number' => $reference,
                'amount' => $amount,
                'is_demo' => $isDemo,
            ],
            $company->id,
        );

        return $document;
    }
}
