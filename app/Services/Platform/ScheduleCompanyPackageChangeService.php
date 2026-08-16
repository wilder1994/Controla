<?php

declare(strict_types=1);

namespace App\Services\Platform;

use App\Enums\BillingCycle;
use App\Enums\CompanyPackageSku;
use App\Enums\EvidenceEventType;
use App\Enums\ManualPaymentIntent;
use App\Models\CommercialPayment;
use App\Models\SecurityCompany;
use App\Models\User;
use App\Services\Pricing\PriceCalculator;
use App\Services\Tenant\AssignCompanyPackageService;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

final class ScheduleCompanyPackageChangeService
{
    public function __construct(
        private readonly RecordLifecycleEvidenceService $evidenceService,
        private readonly PriceCalculator $priceCalculator,
        private readonly RegisterCommercialPaymentService $paymentService,
        private readonly AssignCompanyPackageService $assignCompanyPackageService,
    ) {}

    public function scheduleWithManualPayment(
        SecurityCompany $company,
        User $actor,
        CompanyPackageSku $sku,
        BillingCycle $cycle,
        string $reference,
        UploadedFile $proof,
    ): CommercialPayment {
        $this->assertCanSchedule($company, $sku, $cycle);

        return DB::transaction(function () use ($company, $actor, $sku, $cycle, $reference, $proof) {
            $quote = $this->priceCalculator->quote($sku->modality(), $sku->size(), $cycle);
            $amount = $cycle === BillingCycle::Annual
                ? (float) $quote->priceAnnual
                : (float) $quote->priceMonthly;

            $fromSku = $company->package_sku?->value;
            $effectiveAt = CarbonImmutable::parse($company->package_ends_at);

            $payment = $this->paymentService->executeManual(
                $company,
                $actor,
                $reference,
                $proof,
                ManualPaymentIntent::PlanChange,
                $amount,
                $cycle,
            );

            $payment->update([
                'metadata' => array_merge($payment->metadata ?? [], [
                    'kind' => 'scheduled_package_change',
                    'from_sku' => $fromSku,
                    'to_sku' => $sku->value,
                    'to_cycle' => $cycle->value,
                    'effective_at' => $effectiveAt->toIso8601String(),
                ]),
            ]);

            $this->persistScheduledChange($company, $sku, $cycle, $effectiveAt, $payment, $amount, $fromSku);

            return $payment->fresh();
        });
    }

    public function scheduleWithLocalCheckout(
        SecurityCompany $company,
        User $actor,
        CompanyPackageSku $sku,
        BillingCycle $cycle,
    ): CommercialPayment {
        $this->assertCanSchedule($company, $sku, $cycle);

        $quote = $this->priceCalculator->quote($sku->modality(), $sku->size(), $cycle);
        $amount = $cycle === BillingCycle::Annual
            ? (float) $quote->priceAnnual
            : (float) $quote->priceMonthly;

        $fromSku = $company->package_sku?->value;
        $effectiveAt = CarbonImmutable::parse($company->package_ends_at);

        return $this->paymentService->initiateLocalCheckout(
            $company,
            $actor,
            ManualPaymentIntent::PlanChange,
            $amount,
            $cycle,
            [
                'kind' => 'scheduled_package_change',
                'from_sku' => $fromSku,
                'to_sku' => $sku->value,
                'to_cycle' => $cycle->value,
                'effective_at' => $effectiveAt->toIso8601String(),
            ],
        );
    }

    public function attachFromCompletedPayment(CommercialPayment $payment): void
    {
        $meta = $payment->metadata ?? [];
        if (($meta['kind'] ?? null) !== 'scheduled_package_change') {
            return;
        }

        $company = $payment->company ?? SecurityCompany::query()->findOrFail($payment->security_company_id);
        $sku = CompanyPackageSku::from((string) $meta['to_sku']);
        $cycle = BillingCycle::from((string) $meta['to_cycle']);
        $effectiveAt = isset($meta['effective_at'])
            ? CarbonImmutable::parse((string) $meta['effective_at'])
            : CarbonImmutable::parse($company->package_ends_at);
        $fromSku = isset($meta['from_sku']) ? (string) $meta['from_sku'] : $company->package_sku?->value;

        $this->persistScheduledChange(
            $company,
            $sku,
            $cycle,
            $effectiveAt,
            $payment,
            (float) $payment->amount,
            $fromSku,
        );
    }

    private function assertCanSchedule(
        SecurityCompany $company,
        CompanyPackageSku $sku,
        BillingCycle $cycle,
    ): void {
        if (! $company->isUpToDate()) {
            throw new \InvalidArgumentException(
                'Sin periodo vigente no se puede programar cambio diferido. Reactive o renueve primero.'
            );
        }

        if ($company->package_sku === $sku && $company->billing_cycle === $cycle) {
            throw new \InvalidArgumentException('El plan seleccionado es el mismo que el actual.');
        }
    }

    private function persistScheduledChange(
        SecurityCompany $company,
        CompanyPackageSku $sku,
        BillingCycle $cycle,
        CarbonImmutable $effectiveAt,
        CommercialPayment $payment,
        float $amount,
        ?string $fromSku,
    ): void {
        $company->update([
            'scheduled_package_sku' => $sku->value,
            'scheduled_billing_cycle' => $cycle->value,
            'scheduled_change_at' => $effectiveAt,
            'scheduled_change_payment_id' => $payment->id,
        ]);

        $this->evidenceService->record(
            EvidenceEventType::PackageChangeScheduled,
            'Cambio de plan programado',
            [
                'from_sku' => $fromSku,
                'to_sku' => $sku->value,
                'to_cycle' => $cycle->value,
                'effective_at' => $effectiveAt->toIso8601String(),
                'payment_id' => $payment->id,
                'amount' => $amount,
            ],
            $company->id,
        );
    }

    public function applyDueChanges(?CarbonImmutable $now = null): int
    {
        $now ??= CarbonImmutable::now();
        $applied = 0;

        SecurityCompany::query()
            ->whereNotNull('scheduled_package_sku')
            ->whereNotNull('scheduled_billing_cycle')
            ->whereNotNull('scheduled_change_at')
            ->where('scheduled_change_at', '<=', $now)
            ->chunkById(50, function ($companies) use (&$applied) {
                foreach ($companies as $company) {
                    $this->applyOne($company);
                    $applied++;
                }
            });

        return $applied;
    }

    public function applyOne(SecurityCompany $company): SecurityCompany
    {
        $sku = CompanyPackageSku::from((string) $company->scheduled_package_sku);
        $cycle = BillingCycle::from((string) $company->scheduled_billing_cycle);
        $startsAt = $company->scheduled_change_at
            ? CarbonImmutable::parse($company->scheduled_change_at)
            : CarbonImmutable::now();

        return DB::transaction(function () use ($company, $sku, $cycle, $startsAt) {
            $this->assignCompanyPackageService->execute($company, $sku, $cycle, $startsAt);

            $company->update([
                'scheduled_package_sku' => null,
                'scheduled_billing_cycle' => null,
                'scheduled_change_at' => null,
                'scheduled_change_payment_id' => null,
            ]);

            $this->evidenceService->record(
                EvidenceEventType::PackageChangeApplied,
                'Cambio de plan aplicado',
                [
                    'sku' => $sku->value,
                    'cycle' => $cycle->value,
                    'started_at' => $startsAt->toIso8601String(),
                ],
                $company->id,
            );

            return $company->fresh();
        });
    }
}
