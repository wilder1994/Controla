<?php

declare(strict_types=1);

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\StoreAppUserRequest;
use App\Models\Client;
use App\Models\StructureAppUser;
use App\Models\StructureMember;
use App\Repositories\StructureAppUserRepository;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class AppUserController extends Controller
{
    public function __construct(
        private readonly StructureAppUserRepository $appUserRepository,
        private readonly TenantContext $tenantContext,
    ) {}

    public function index(): View
    {
        abort_unless(auth()->user()?->can('client.app_users.manage'), 403);

        $clientId = (int) $this->tenantContext->clientId();
        $appUsers = $this->appUserRepository->paginateForClient($clientId);
        $client = Client::query()->find($clientId);

        return view('modules.client.app-users.index', compact('appUsers', 'client'));
    }

    public function create(Request $request): View
    {
        abort_unless(auth()->user()?->can('client.app_users.manage'), 403);

        $clientId = (int) $this->tenantContext->clientId();
        $membersQuery = StructureMember::query()
            ->shareable()
            ->with(['structure.installation', 'structure.parent'])
            ->whereDoesntHave('appUser')
            ->where('is_active', true)
            ->orderBy('last_name')
            ->orderBy('first_name');
        $allowed = $this->tenantContext->installationIds();
        if ($allowed !== null) {
            $membersQuery->whereHas('structure', fn ($q) => $q->whereIn('installation_id', $allowed));
        }
        $members = $membersQuery->get();
        $client = Client::query()->find((int) $this->tenantContext->clientId());
        $selectedMemberId = $request->integer('member_id') ?: null;

        return view('modules.client.app-users.create', compact('members', 'client', 'selectedMemberId'));
    }

    public function store(StoreAppUserRequest $request): RedirectResponse
    {
        $clientId = (int) $this->tenantContext->clientId();

        if ($this->appUserRepository->usernameExists($clientId, $request->validated('username'))) {
            return back()->withErrors(['username' => 'El usuario ya existe en este cliente.'])->withInput();
        }

        $member = StructureMember::query()->findOrFail((int) $request->validated('member_id'));

        StructureAppUser::query()->create([
            'client_id' => $clientId,
            'member_id' => $member->id,
            'username' => $request->validated('username'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        $member->update(['has_app_access' => true]);

        return redirect()
            ->route('client.app-users.index')
            ->with('success', 'Acceso de persona creado correctamente.');
    }
}
