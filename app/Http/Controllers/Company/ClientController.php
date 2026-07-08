<?php

declare(strict_types=1);

namespace App\Http\Controllers\Company;

use App\Domain\Tenant\Data\CreateClientData;
use App\Enums\ClientPlanTier;
use App\Http\Controllers\Controller;
use App\Http\Requests\Company\StoreClientRequest;
use App\Http\Requests\Company\UpdateClientRequest;
use App\Models\Client;
use App\Repositories\ClientRepository;
use App\Services\Tenant\CreateClientService;
use App\Services\Tenant\UpdateClientService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ClientController extends Controller
{
    public function __construct(
        private readonly ClientRepository $clientRepository,
        private readonly CreateClientService $createClientService,
        private readonly UpdateClientService $updateClientService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Client::class);

        $companyId = $this->companyId($request);
        $clients = $this->clientRepository->paginateForCompany($companyId);

        return view('modules.company.clients.index', compact('clients'));
    }

    public function create(): View
    {
        $this->authorize('create', Client::class);

        $planTiers = ClientPlanTier::options();

        return view('modules.company.clients.create', compact('planTiers'));
    }

    public function store(StoreClientRequest $request): RedirectResponse
    {
        $companyId = $this->companyId($request);

        $client = $this->createClientService->execute(new CreateClientData(
            securityCompanyId: $companyId,
            name: $request->validated('name'),
            slug: $request->validated('slug'),
            loginSuffix: $request->validated('login_suffix'),
            planTier: ClientPlanTier::from($request->validated('plan_tier')),
            accessUrl: $request->validated('access_url'),
            isActive: $request->boolean('is_active', true),
        ));

        return redirect()
            ->route('company.clients.show', $client)
            ->with('success', "Cliente «{$client->name}» creado correctamente.");
    }

    public function show(Request $request, Client $client): View
    {
        $this->authorize('view', $client);
        $this->assertCompanyOwnership($request, $client);

        $client->loadCount('assignments');

        return view('modules.company.clients.show', compact('client'));
    }

    public function edit(Request $request, Client $client): View
    {
        $this->authorize('update', $client);
        $this->assertCompanyOwnership($request, $client);

        $planTiers = ClientPlanTier::options();

        return view('modules.company.clients.edit', compact('client', 'planTiers'));
    }

    public function update(UpdateClientRequest $request, Client $client): RedirectResponse
    {
        $this->assertCompanyOwnership($request, $client);

        $this->updateClientService->execute($client, $request->validated());

        return redirect()
            ->route('company.clients.show', $client)
            ->with('success', 'Cliente actualizado.');
    }

    public function select(Request $request): View
    {
        $user = $request->user();

        $clients = $user->hasRole('super-admin')
            ? $this->clientRepository->activeAll()
                ->filter(fn (Client $client) => $user->can('operate', $client))
            : $this->clientRepository->activeForCompany((int) $user->security_company_id)
                ->filter(fn (Client $client) => $user->can('operate', $client));

        return view('modules.company.clients.select', compact('clients'));
    }

    public function activate(Request $request, Client $client): RedirectResponse
    {
        $this->authorize('operate', $client);

        $request->session()->put(config('tenancy.session.active_client_key'), $client->id);

        return redirect()
            ->route('access.dashboard')
            ->with('success', "Operando en: {$client->name}");
    }

    private function companyId(Request $request): int
    {
        $user = $request->user();

        if ($user->hasRole('super-admin')) {
            abort(403, 'Use el panel de plataforma para gestionar empresas.');
        }

        return (int) $user->security_company_id;
    }

    private function assertCompanyOwnership(Request $request, Client $client): void
    {
        if ($request->user()->hasRole('super-admin')) {
            return;
        }

        abort_unless(
            (int) $request->user()->security_company_id === (int) $client->security_company_id,
            403
        );
    }
}
