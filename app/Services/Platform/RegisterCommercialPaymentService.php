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
use Illuminate\Support\Str;

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
        $this->assertAcceptanceCompleted($company);

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
                'initiated_by_user_id' => $recordedBy->id,
                'metadata' => ['source' => 'admin_manual'],
            ]);

            $this->finalizeCompletedPayment($payment, $recordedBy);

            return $payment;
        });
    }

    public function initiateLocalCheckout(SecurityCompany $company, User $initiatedBy): CommercialPayment
    {
        $this->assertAcceptanceCompleted($company);
        $this->assertLocalGatewayDriver();

        return CommercialPayment::query()->create([
            'security_company_id' => $company->id,
            'amount' => $company->contractedAmount(),
            'currency' => config('billing.currency', 'COP'),
            'billing_cycle' => $company->billing_cycle?->value,
            'method' => PaymentMethod::Gateway,
            'gateway_driver' => 'local',
            'gateway_transaction_id' => (string) Str::uuid(),
            'gateway_status' => 'checkout_created',
            'status' => PaymentStatus::Pending,
            'initiated_by_user_id' => $initiatedBy->id,
            'metadata' => ['source' => 'local_simulator'],
        ]);
    }

    public function completeLocalCheckout(CommercialPayment $payment, User $actor): CommercialPayment
    {
        $this->assertLocalPendingPayment($payment);

        return DB::transaction(function () use ($payment, $actor) {
            $now = CarbonImmutable::now();

            $payment->update([
                'status' => PaymentStatus::Completed,
                'gateway_status' => 'approved',
                'reference' => 'LOCAL-'.Str::upper(Str::substr($payment->gateway_transaction_id ?? '', 0, 12)),
                'paid_at' => $now,
                'recorded_by_user_id' => $actor->id,
            ]);

            $this->finalizeCompletedPayment($payment->fresh(), $actor);

            return $payment->fresh();
        });
    }

    public function failLocalCheckout(CommercialPayment $payment, User $actor): CommercialPayment
    {
        $this->assertLocalPendingPayment($payment);

        $payment->update([
            'status' => PaymentStatus::Failed,
            'gateway_status' => 'rejected',
            'metadata' => array_merge($payment->metadata ?? [], [
                'rejected_by_user_id' => $actor->id,
                'rejected_at' => CarbonImmutable::now()->toIso8601String(),
            ]),
        ]);

        return $payment->fresh();
    }

    private function finalizeCompletedPayment(CommercialPayment $payment, User $recordedBy): void
    {
        $company = $payment->company ?? SecurityCompany::query()->findOrFail($payment->security_company_id);

        $this->evidenceService->record(
            EvidenceEventType::PaymentRecorded,
            $payment->method === PaymentMethod::Manual
                ? 'Pago manual registrado'
                : 'Pago online registrado (simulador local)',
            [
                'payment_id' => $payment->id,
                'amount' => (float) $payment->amount,
                'reference' => $payment->reference,
                'method' => $payment->method->value,
                'gateway_driver' => $payment->gateway_driver,
            ],
            $company->id,
        );

        $this->issueDemoInvoiceService->execute($company, $recordedBy, $payment);
    }

    private function assertAcceptanceCompleted(SecurityCompany $company): void
    {
        if (! $company->hasCompletedAcceptance()) {
            throw new \InvalidArgumentException('La empresa debe completar la aceptación contractual antes del pago.');
        }
    }

    private function assertLocalGatewayDriver(): void
    {
        if (config('billing.gateway.driver') !== 'local') {
            throw new \RuntimeException(
                'Solo el driver local está implementado. Use BILLING_GATEWAY_DRIVER=local para pruebas sin proveedor.'
            );
        }
    }

    private function assertLocalPendingPayment(CommercialPayment $payment): void
    {
        if ($payment->gateway_driver !== 'local') {
            throw new \InvalidArgumentException('Este pago no pertenece al simulador local.');
        }

        if ($payment->status !== PaymentStatus::Pending) {
            throw new \InvalidArgumentException('El pago ya no está pendiente de confirmación.');
        }
    }
}
