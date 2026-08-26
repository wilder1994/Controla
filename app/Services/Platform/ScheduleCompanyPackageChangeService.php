<?php

declare(strict_types=1);

namespace App\Services\Platform;

use App\Domain\Pricing\Data\AccessSeatSplit;
use App\Enums\BillingCycle;
use App\Enums\CompanyPackageSku;
use App\Enums\EvidenceEventType;
use App\Enums\ManualPaymentIntent;
use App\Enums\SupervisionPackageSku;
use App\Models\CommercialPayment;
use App\Models\SecurityCompany;
use App\Models\User;
use App\Services\Pricing\PriceCalculator;
use App\Services\Tenant\AssignCompanyPackageService;
use App\Services\Tenant\AssignCompanySupervisionPackageService;
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
        private readonly AssignCompanySupervisionPackageService $assignCompanySupervisionPackageService,
    ) {}

    public function scheduleWithManualPayment(
        SecurityCompany $company,
        User $actor,
        CompanyPackageSku $sku,
        BillingCycle $cycle,
        string $reference,
        UploadedFile $proof,
        ?AccessSeatSplit $seats = null,
        ?SupervisionPackageSku $supervisionSku = null,
        bool $includeSupervision = false,
    ): CommercialPayment {
        $seats ??= AccessSeatSplit::fromSku($sku);
        $this->assertCanSchedule($company, $sku, $cycle, $seats, $includeSupervision, $supervisionSku);

        return DB::transaction(function () use ($company, $actor, $sku, $cycle, $reference, $proof, $seats, $supervisionSku, $includeSupervision) {
            $amount = $this->amountDue($seats, $cycle, $includeSupervision ? $supervisionSku : null, $company);
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
                'metadata' => array_merge($payment->metadata ?? [], $this->meta(
                    $fromSku,
                    $sku,
                    $cycle,
                    $effectiveAt,
                    $seats,
                    $includeSupervision,
                    $supervisionSku,
                )),
            ]);

            $this->persistScheduledChange(
                $company,
                $sku,
                $cycle,
                $effectiveAt,
                $payment,
                $amount,
                $fromSku,
                $seats,
                $includeSupervision,
                $supervisionSku,
            );

            return $payment->fresh();
        });
    }

    public function scheduleWithLocalCheckout(
        SecurityCompany $company,
        User $actor,
        CompanyPackageSku $sku,
        BillingCycle $cycle,
        ?AccessSeatSplit $seats = null,
        bool $includeSupervision = false,
        ?SupervisionPackageSku $supervisionSku = null,
    ): CommercialPayment {
        $seats ??= AccessSeatSplit::fromSku($sku);
        $this->assertCanSchedule($company, $sku, $cycle, $seats, $includeSupervision, $supervisionSku);

        $amount = $this->amountDue($seats, $cycle, $includeSupervision ? $supervisionSku : null, $company);
        $fromSku = $company->package_sku?->value;
        $effectiveAt = CarbonImmutable::parse($company->package_ends_at);

        return $this->paymentService->initiateLocalCheckout(
            $company,
            $actor,
            ManualPaymentIntent::PlanChange,
            $amount,
            $cycle,
            $this->meta($fromSku, $sku, $cycle, $effectiveAt, $seats, $includeSupervision, $supervisionSku),
        );
    }

    public function scheduleSupervisionRemoval(SecurityCompany $company): SecurityCompany
    {
        if (! $company->isUpToDate()) {
            throw new \InvalidArgumentException(
                'Sin periodo vigente no se puede programar el retiro de Supervisión.',
            );
        }

        $effectiveAt = CarbonImmutable::parse($company->package_ends_at);
        $company->update([
            'scheduled_supervision_package_sku' => 'none',
            'scheduled_change_at' => $effectiveAt,
        ]);

        $this->evidenceService->record(
            EvidenceEventType::PackageChangeScheduled,
            'Retiro de Supervisión programado',
            ['effective_at' => $effectiveAt->toIso8601String()],
            $company->id,
        );

        return $company->fresh();
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
        $seats = new AccessSeatSplit(
            (int) ($meta['manual_seats'] ?? $sku->size()),
            (int) ($meta['hardware_seats'] ?? 0),
        );
        $includeSupervision = (bool) ($meta['include_supervision'] ?? false);
        $supervisionSku = isset($meta['to_supervision_sku']) && $meta['to_supervision_sku'] !== '' && $meta['to_supervision_sku'] !== 'none'
            ? SupervisionPackageSku::from((string) $meta['to_supervision_sku'])
            : null;
        if (($meta['to_supervision_sku'] ?? null) === 'none') {
            $includeSupervision = true;
            $supervisionSku = null;
        }

        $this->persistScheduledChange(
            $company,
            $sku,
            $cycle,
            $effectiveAt,
            $payment,
            (float) $payment->amount,
            $fromSku,
            $seats,
            $includeSupervision,
            $supervisionSku,
        );
    }

    public function applyDueChanges(?CarbonImmutable $now = null): int
    {
        $now ??= CarbonImmutable::now();
        $applied = 0;

        SecurityCompany::query()
            ->whereNotNull('scheduled_change_at')
            ->where('scheduled_change_at', '<=', $now)
            ->where(function ($q): void {
                $q->whereNotNull('scheduled_package_sku')
                    ->orWhereNotNull('scheduled_supervision_package_sku');
            })
            ->chunkById(50, function ($companies) use (&$applied): void {
                foreach ($companies as $company) {
                    $this->applyOne($company);
                    $applied++;
                }
            });

        return $applied;
    }

    public function applyOne(SecurityCompany $company): SecurityCompany
    {
        $sku = $company->scheduled_package_sku
            ? CompanyPackageSku::from((string) $company->scheduled_package_sku)
            : ($company->package_sku ?? CompanyPackageSku::Pack10Manual);
        $cycle = BillingCycle::from((string) ($company->scheduled_billing_cycle ?? $company->billing_cycle?->value ?? 'monthly'));
        $startsAt = $company->scheduled_change_at
            ? CarbonImmutable::parse($company->scheduled_change_at)
            : CarbonImmutable::now();
        $hasAccessChange = $company->scheduled_package_sku !== null;
        $manual = (int) ($company->scheduled_manual_seats ?? $company->package_manual_seats ?? $sku->size());
        $hardware = (int) ($company->scheduled_hardware_seats ?? $company->package_hardware_seats ?? 0);
        $seats = $hasAccessChange ? new AccessSeatSplit($manual, $hardware) : $company->accessSeats();
        $scheduledSup = $company->scheduled_supervision_package_sku;

        return DB::transaction(function () use ($company, $sku, $cycle, $startsAt, $seats, $scheduledSup, $hasAccessChange) {
            if ($hasAccessChange) {
                $this->assignCompanyPackageService->execute($company, $sku, $cycle, $startsAt, $seats);
            }

            if ($scheduledSup !== null) {
                $supSku = $scheduledSup === 'none' ? null : SupervisionPackageSku::from($scheduledSup);
                $offer = SupervisionPackageSku::offerForAccessSize($seats->size());
                $discount = ($supSku !== null && $supSku === $offer)
                    ? $this->priceCalculator->volumeDiscountFor($seats->size())
                    : null;
                $this->assignCompanySupervisionPackageService->execute($company->fresh(), $supSku, $discount);
            }

            $company->update([
                'scheduled_package_sku' => null,
                'scheduled_manual_seats' => null,
                'scheduled_hardware_seats' => null,
                'scheduled_supervision_package_sku' => null,
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

    private function amountDue(
        AccessSeatSplit $seats,
        BillingCycle $cycle,
        ?SupervisionPackageSku $supervisionSku,
        SecurityCompany $company,
    ): float {
        $sameAccess = $company->sameAccessAs($seats, $cycle);

        $amount = 0.0;
        if (! $sameAccess) {
            $access = $this->priceCalculator->quoteAccess($seats, $cycle);
            $amount += $cycle === BillingCycle::Annual ? $access->priceAnnual : $access->priceMonthly;
        }

        if ($supervisionSku !== null) {
            $sup = $this->priceCalculator->quoteSupervisionForAccess($supervisionSku, $seats->size(), $cycle);
            $amount += $cycle === BillingCycle::Annual ? $sup->priceAnnual : $sup->priceMonthly;
        }

        if ($amount <= 0) {
            throw new \InvalidArgumentException('No hay un cobro asociado a este cambio.');
        }

        return $amount;
    }

    /** @return array<string, mixed> */
    private function meta(
        ?string $fromSku,
        CompanyPackageSku $sku,
        BillingCycle $cycle,
        CarbonImmutable $effectiveAt,
        AccessSeatSplit $seats,
        bool $includeSupervision,
        ?SupervisionPackageSku $supervisionSku,
    ): array {
        return [
            'kind' => 'scheduled_package_change',
            'from_sku' => $fromSku,
            'to_sku' => $sku->value,
            'to_cycle' => $cycle->value,
            'effective_at' => $effectiveAt->toIso8601String(),
            'manual_seats' => $seats->manual,
            'hardware_seats' => $seats->hardware,
            'include_supervision' => $includeSupervision,
            'to_supervision_sku' => $includeSupervision ? ($supervisionSku?->value ?? 'none') : null,
        ];
    }

    private function persistScheduledChange(
        SecurityCompany $company,
        CompanyPackageSku $sku,
        BillingCycle $cycle,
        CarbonImmutable $effectiveAt,
        CommercialPayment $payment,
        float $amount,
        ?string $fromSku,
        AccessSeatSplit $seats,
        bool $includeSupervision,
        ?SupervisionPackageSku $supervisionSku,
    ): void {
        $payload = [
            'scheduled_change_at' => $effectiveAt,
            'scheduled_change_payment_id' => $payment->id,
        ];

        if (! $company->sameAccessAs($seats, $cycle)) {
            $payload['scheduled_package_sku'] = $sku->value;
            $payload['scheduled_manual_seats'] = $seats->manual;
            $payload['scheduled_hardware_seats'] = $seats->hardware;
            $payload['scheduled_billing_cycle'] = $cycle->value;
        }

        if ($includeSupervision) {
            $payload['scheduled_supervision_package_sku'] = $supervisionSku?->value ?? 'none';
        }

        $company->update($payload);

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

    private function assertCanSchedule(
        SecurityCompany $company,
        CompanyPackageSku $sku,
        BillingCycle $cycle,
        AccessSeatSplit $seats,
        bool $includeSupervision,
        ?SupervisionPackageSku $supervisionSku,
    ): void {
        if (! $company->isUpToDate()) {
            throw new \InvalidArgumentException(
                'Sin periodo vigente no se puede programar cambio diferido. Reactive o renueve primero.'
            );
        }

        $sameAccess = $company->sameAccessAs($seats, $cycle);

        $sameSup = ! $includeSupervision
            || $company->supervision_package_sku === $supervisionSku;

        if ($sameAccess && $sameSup) {
            throw new \InvalidArgumentException('El plan seleccionado es el mismo que el actual.');
        }

        if ($supervisionSku !== null && $seats->size() < 5) {
            throw new \InvalidArgumentException('El paquete de 1 cliente de Accesos no incluye Supervisión.');
        }
    }
}
