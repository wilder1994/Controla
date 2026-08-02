<?php

declare(strict_types=1);

namespace App\Services\Platform;

use App\Enums\EvidenceEventType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\CommercialPayment;
use App\Models\SecurityCompany;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class RegisterCommercialPaymentService
{
    public function __construct(
        private readonly RecordLifecycleEvidenceService $evidenceService,
        private readonly IssueDemoInvoiceService $issueDemoInvoiceService,
    ) {}

    public function executeManual(
        SecurityCompany $company,
        User $recordedBy,
        ?string $reference = null,
    ): CommercialPayment {
        if (! $company->hasCompletedAcceptance()) {
            throw new \InvalidArgumentException('La empresa debe completar la aceptación contractual antes del pago.');
        }

        return DB::transaction(function () use ($company, $recordedBy, $reference) {
            $now = CarbonImmutable::now();
            $amount = $company->contractedAmount();

            $payment = CommercialPayment::query()->create([
                'security_company_id' => $company->id,
                'amount' => $amount,
                'currency' => config('billing.currency', 'COP'),
                'billing_cycle' => $company->billing_cycle?->value,
                'method' => PaymentMethod::Manual,
                'status' => PaymentStatus::Completed,
                'reference' => $reference ?: 'MANUAL-'.$now->format('YmdHis'),
                'paid_at' => $now,
                'recorded_by_user_id' => $recordedBy->id,
                'metadata' => ['source' => 'admin_manual'],
            ]);

            $this->evidenceService->record(
                EvidenceEventType::PaymentRecorded,
                'Pago manual registrado',
                [
                    'payment_id' => $payment->id,
                    'amount' => $amount,
                    'reference' => $payment->reference,
                ],
                $company->id,
            );

            $this->issueDemoInvoiceService->execute($company, $recordedBy, $payment);

            return $payment;
        });
    }
}
