<?php

declare(strict_types=1);

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\CommercialPayment;
use App\Models\User;
use App\Services\Platform\RegisterCommercialPaymentService;
use App\Support\Billing\CommercialPaymentAuthorization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class CheckoutController extends Controller
{
    public function __construct(
        private readonly RegisterCommercialPaymentService $paymentService,
    ) {}

    public function show(Request $request, CommercialPayment $payment): View|RedirectResponse
    {
        CommercialPaymentAuthorization::authorize($request->user(), $payment);

        if ($payment->status->value !== 'pending') {
            return $this->redirectAfterCheckout($payment, $request->user());
        }

        $payment->load('company');

        return view('modules.billing.checkout', [
            'payment' => $payment,
            'company' => $payment->company,
            'isSimulated' => $payment->gateway_driver === 'local',
        ]);
    }

    public function approve(Request $request, CommercialPayment $payment): RedirectResponse
    {
        CommercialPaymentAuthorization::authorize($request->user(), $payment);

        try {
            $this->paymentService->completeLocalCheckout($payment, $request->user());
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->route('billing.checkout.show', $payment)
                ->with('warning', $e->getMessage());
        }

        return $this->redirectAfterCheckout($payment->fresh(), $request->user())
            ->with('success', 'Pago aprobado. Factura demo generada en el expediente.');
    }

    public function reject(Request $request, CommercialPayment $payment): RedirectResponse
    {
        CommercialPaymentAuthorization::authorize($request->user(), $payment);

        try {
            $this->paymentService->failLocalCheckout($payment, $request->user());
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->route('billing.checkout.show', $payment)
                ->with('warning', $e->getMessage());
        }

        return $this->redirectAfterCheckout($payment->fresh(), $request->user())
            ->with('warning', 'Pago rechazado en el simulador local.');
    }

    private function redirectAfterCheckout(CommercialPayment $payment, ?User $user): RedirectResponse
    {
        if ($user?->can('platform.documents.manage')) {
            return redirect()->route('admin.documents.expedientes.show', $payment->security_company_id);
        }

        return redirect()->route('company.billing.index');
    }
}
