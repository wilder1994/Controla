<?php

declare(strict_types=1);

namespace App\Http\Controllers\Company;

use App\Domain\Tenant\Data\CreateClientData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Company\StoreClientRequest;
use App\Http\Requests\Company\UpdateClientRequest;
use App\Models\Client;
use App\Repositories\ClientRepository;
use App\Services\Tenant\BuildClientExpedienteService;
use App\Services\Tenant\CreateClientService;
use App\Services\Tenant\UpdateClientService;
use App\Support\Platform\ActingCompanyResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ClientController extends Controller
{
    public function __construct(
        private readonly ClientRepository $clientRepository,
        private readonly CreateClientService $createClientService,
        private readonly UpdateClientService $updateClientService,
        private readonly BuildClientExpedienteService $buildClientExpedienteService,
    ) {}

    public function index(Request $request): View
    {
        $operateMode = $request->query('modo') === 'operar';
        $user = $request->user();
        $search = $request->string('q')->trim()->toString();
        $status = $request->string('status')->toString();

        if ($operateMode) {
            abort_unless($user?->can('access.dashboard'), 403);
            if (! in_array($status, ['active', 'inactive'], true)) {
                $status = 'active';
            }
        } else {
            $this->authorize('viewAny', Client::class);
            if (! in_array($status, ['active', 'inactive'], true)) {
                $status = 'all';
            }
        }

        if ($user->hasRole('super-admin')) {
            $companyId = app(ActingCompanyResolver::class)->id($user);
            abort_unless($companyId !== null || $operateMode, 403, 'Use el panel de plataforma para gestionar empresas.');

            if ($companyId !== null) {
                if ($operateMode && $status === 'all') {
                    $status = 'active';
                }

                $clients = $this->clientRepository->paginateForCompany(
                    $companyId,
                    15,
                    $search !== '' ? $search : null,
                    $status !== 'all' ? $status : null,
                );
                $metrics = $this->clientRepository->metricsForCompany($companyId);

                return view('modules.company.clients.index', compact(
                    'clients',
                    'metrics',
                    'search',
                    'status',
                    'operateMode',
                ));
            }

            $clients = $this->clientRepository->paginateOperableForUser(
                $user,
                15,
                $search !== '' ? $search : null,
                $status,
            );

            return view('modules.company.clients.index', [
                'clients' => $clients,
                'metrics' => null,
                'search' => $search,
                'status' => $status,
                'operateMode' => true,
            ]);
        }

        if ($user->hasRole('company-admin') && $user->security_company_id) {
            $companyId = (int) $user->security_company_id;

            if ($operateMode && $status === 'all') {
                $status = 'active';
            }

            $clients = $this->clientRepository->paginateForCompany(
                $companyId,
                15,
                $search !== '' ? $search : null,
                $status !== 'all' ? $status : null,
            );
            $metrics = $this->clientRepository->metricsForCompany($companyId);

            return view('modules.company.clients.index', compact(
                'clients',
                'metrics',
                'search',
                'status',
            ) + ['operateMode' => $operateMode]);
        }

        if ($operateMode) {
            $clients = $this->clientRepository->paginateOperableForUser(
                $user,
                15,
                $search !== '' ? $search : null,
                $status,
            );

            return view('modules.company.clients.index', [
                'clients' => $clients,
                'metrics' => null,
                'search' => $search,
                'status' => $status,
                'operateMode' => true,
            ]);
        }

        abort(403);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $this->authorize('create', Client::class);

        $companyId = $this->companyId($request);
        $metrics = $this->clientRepository->metricsForCompany($companyId);

        if ($metrics['is_quota_full']) {
            return redirect()
                ->route('company.clients.index')
                ->with('error', 'Has alcanzado el cupo de clientes de tu paquete. Solicita ampliación a plataforma.');
        }

        return view('modules.company.clients.create', compact('metrics'));
    }

    public function store(StoreClientRequest $request): RedirectResponse
    {
        $companyId = $this->companyId($request);

        $client = $this->createClientService->execute(new CreateClientData(
            securityCompanyId: $companyId,
            name: $request->validated('name'),
            slug: $request->validated('slug'),
            loginSuffix: $request->validated('login_suffix'),
            address: $request->validated('address'),
            city: $request->validated('city'),
            department: $request->validated('department'),
            latitude: $request->filled('latitude') ? (float) $request->validated('latitude') : null,
            longitude: $request->filled('longitude') ? (float) $request->validated('longitude') : null,
            accessUrl: $request->validated('access_url'),
            isActive: $request->boolean('is_active', true),
            serviceStartedAt: $request->validated('service_started_at'),
        ));

        return redirect()
            ->route('company.clients.show', $client)
            ->with('success', "Cliente «{$client->name}» creado correctamente.");
    }

    public function show(Request $request, Client $client): View
    {
        $this->authorize('view', $client);
        $this->assertCompanyOwnership($request, $client);

        $client->load(['securityCompany']);
        $expediente = $this->buildClientExpedienteService->execute($client);

        return view('modules.company.clients.show', [
            'client' => $client,
            'expediente' => $expediente,
            'canOperate' => $request->user()->can('operate', $client),
            'canUpdate' => $request->user()->can('update', $client),
            'canOperateClientPanel' => $request->user()->can('client.structures.manage')
                && $request->user()->can('operate', $client),
        ]);
    }

    public function edit(Request $request, Client $client): View
    {
        $this->authorize('update', $client);
        $this->assertCompanyOwnership($request, $client);

        $client->load('securityCompany');

        return view('modules.company.clients.edit', [
            'client' => $client,
            'canOperate' => $request->user()->can('operate', $client),
            'canUpdate' => true,
            'canOperateClientPanel' => $request->user()->can('client.structures.manage')
                && $request->user()->can('operate', $client),
        ]);
    }

    public function update(UpdateClientRequest $request, Client $client): RedirectResponse
    {
        $this->assertCompanyOwnership($request, $client);

        $this->updateClientService->execute($client, $request->validated());

        return redirect()
            ->route('company.clients.show', $client)
            ->with('success', 'Cliente actualizado.');
    }

    public function activate(Request $request, Client $client): RedirectResponse
    {
        $this->authorize('operate', $client);

        $request->session()->put(config('tenancy.session.active_client_key'), $client->id);

        return redirect()
            ->route('access.dashboard')
            ->with('success', "Operando portería en: {$client->name}");
    }

    public function operateClient(Request $request, Client $client): RedirectResponse
    {
        $this->authorize('operate', $client);
        abort_unless($request->user()?->can('client.structures.manage'), 403);

        $request->session()->put(config('tenancy.session.active_client_key'), $client->id);

        return redirect()
            ->route('client.dashboard')
            ->with('success', "Operando panel del conjunto: {$client->name}");
    }

    private function companyId(Request $request): int
    {
        return app(ActingCompanyResolver::class)->requireId($request->user());
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
