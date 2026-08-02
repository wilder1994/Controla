<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StoreManualPaymentRequest;
use App\Http\Requests\Platform\StoreSubscriptionAcceptanceRequest;
use App\Models\DocumentRetentionSeries;
use App\Models\LegalCorpusVersion;
use App\Models\SecurityCompany;
use App\Services\Platform\PlatformDocumentsHubService;
use App\Services\Platform\RecordSubscriptionAcceptanceService;
use App\Services\Platform\RegisterCommercialPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class DocumentController extends Controller
{
    public function __construct(
        private readonly PlatformDocumentsHubService $hubService,
        private readonly RecordSubscriptionAcceptanceService $acceptanceService,
        private readonly RegisterCommercialPaymentService $paymentService,
    ) {}

    public function index(): View
    {
        abort_unless(auth()->user()?->can('platform.documents.view'), 403);

        $kpis = $this->hubService->hubKpis();

        return view('modules.admin.documents.index', compact('kpis'));
    }

    public function normativa(): View
    {
        abort_unless(auth()->user()?->can('platform.documents.view'), 403);

        $documents = LegalCorpusVersion::query()
            ->orderBy('type')
            ->orderByDesc('effective_from')
            ->get()
            ->groupBy(fn (LegalCorpusVersion $v) => $v->type->value);

        return view('modules.admin.documents.normativa', compact('documents'));
    }

    public function trd(): View
    {
        abort_unless(auth()->user()?->can('platform.documents.view'), 403);

        $series = DocumentRetentionSeries::query()
            ->orderBy('sort_order')
            ->orderBy('series')
            ->get();

        return view('modules.admin.documents.trd', compact('series'));
    }

    public function expedientes(): View
    {
        abort_unless(auth()->user()?->can('platform.documents.view'), 403);

        $companies = $this->hubService->expedientesListing();

        return view('modules.admin.documents.expedientes', compact('companies'));
    }

    public function showExpediente(SecurityCompany $company): View
    {
        abort_unless(auth()->user()?->can('platform.documents.view'), 403);

        $detail = $this->hubService->expedienteDetail($company);
        $corpus = LegalCorpusVersion::currentForAllTypes();

        return view('modules.admin.documents.expediente', [
            'company' => $company,
            'timeline' => $detail['timeline'],
            'documents' => $detail['documents'],
            'acceptance' => $detail['acceptance'],
            'payments' => $detail['payments'],
            'corpus' => $corpus,
        ]);
    }

    public function storeAcceptance(
        StoreSubscriptionAcceptanceRequest $request,
        SecurityCompany $company,
    ): RedirectResponse {
        if ($company->hasCompletedAcceptance()) {
            return redirect()
                ->route('admin.documents.expedientes.show', $company)
                ->with('warning', 'Este suscriptor ya tiene una aceptación registrada.');
        }

        $this->acceptanceService->execute(
            $company,
            $request->user(),
            $request->validated('representative_name'),
            $request->validated('representative_role'),
            $request->validated('representative_document_type'),
            $request->validated('representative_document_number'),
            $request,
        );

        return redirect()
            ->route('admin.documents.expedientes.show', $company)
            ->with('success', 'Aceptación contractual registrada con evidencia.');
    }

    public function storeManualPayment(
        StoreManualPaymentRequest $request,
        SecurityCompany $company,
    ): RedirectResponse {
        try {
            $this->paymentService->executeManual(
                $company,
                $request->user(),
                $request->validated('reference'),
            );
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->route('admin.documents.expedientes.show', $company)
                ->with('warning', $e->getMessage());
        }

        return redirect()
            ->route('admin.documents.expedientes.show', $company)
            ->with('success', 'Pago manual registrado. Factura demo generada en expediente.');
    }
}
