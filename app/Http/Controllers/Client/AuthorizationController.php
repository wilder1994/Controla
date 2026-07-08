<?php

declare(strict_types=1);

namespace App\Http\Controllers\Client;

use App\Domain\Structure\Data\CreateAuthorizationData;
use App\Enums\VisitorCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\StoreAuthorizationRequest;
use App\Models\Structure;
use App\Models\StructureMember;
use App\Models\VisitorPreAuthorization;
use App\Repositories\VisitorPreAuthorizationRepository;
use App\Services\Structure\CreateAuthorizationService;
use App\Services\Structure\ImportAuthorizationsService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

final class AuthorizationController extends Controller
{
    public function __construct(
        private readonly VisitorPreAuthorizationRepository $authorizationRepository,
        private readonly CreateAuthorizationService $createAuthorizationService,
        private readonly ImportAuthorizationsService $importAuthorizationsService,
        private readonly TenantContext $tenantContext,
    ) {}

    public function index(): View
    {
        abort_unless(auth()->user()?->can('client.authorizations.manage'), 403);

        $clientId = (int) $this->tenantContext->clientId();
        $authorizations = $this->authorizationRepository->paginateForClient($clientId);
        $categories = VisitorCategory::options();

        return view('modules.client.authorizations.index', compact('authorizations', 'categories'));
    }

    public function create(): View
    {
        abort_unless(auth()->user()?->can('client.authorizations.manage'), 403);

        $structures = Structure::query()->orderBy('name')->get();
        $members = StructureMember::query()->orderBy('last_name')->get();
        $categories = VisitorCategory::options();

        return view('modules.client.authorizations.create', compact('structures', 'members', 'categories'));
    }

    public function store(StoreAuthorizationRequest $request): RedirectResponse
    {
        $clientId = (int) $this->tenantContext->clientId();

        $this->createAuthorizationService->execute(new CreateAuthorizationData(
            clientId: $clientId,
            structureId: (int) $request->validated('structure_id'),
            memberId: $request->validated('member_id'),
            visitorName: $request->validated('visitor_name'),
            visitorDocument: $request->validated('visitor_document'),
            visitorCategory: VisitorCategory::from($request->validated('visitor_category')),
            validForDate: $request->validated('valid_for_date'),
            notes: $request->validated('notes'),
        ));

        return redirect()
            ->route('client.authorizations.index')
            ->with('success', 'Autorización creada correctamente.');
    }

    public function importForm(): View
    {
        abort_unless(auth()->user()?->can('client.authorizations.manage'), 403);

        return view('modules.client.authorizations.import');
    }

    public function import(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->can('client.authorizations.manage'), 403);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        $clientId = (int) $this->tenantContext->clientId();
        $importer = $this->importAuthorizationsService->forClient($clientId);

        Excel::import($importer, $request->file('file'));

        $message = "Importadas {$importer->importedCount()} autorizaciones.";
        $errors = $importer->errors();

        if (count($errors) > 0) {
            return redirect()
                ->route('client.authorizations.index')
                ->with('warning', $message.' Algunas filas tuvieron errores: '.implode(' ', array_slice($errors, 0, 3)));
        }

        return redirect()
            ->route('client.authorizations.index')
            ->with('success', $message);
    }
}
