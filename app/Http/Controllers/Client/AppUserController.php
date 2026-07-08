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

    public function create(): View
    {
        abort_unless(auth()->user()?->can('client.app_users.manage'), 403);

        $members = StructureMember::query()->where('has_app_access', true)->orderBy('last_name')->get();
        $client = Client::query()->find((int) $this->tenantContext->clientId());

        return view('modules.client.app-users.create', compact('members', 'client'));
    }

    public function store(StoreAppUserRequest $request): RedirectResponse
    {
        $clientId = (int) $this->tenantContext->clientId();

        if ($this->appUserRepository->usernameExists($clientId, $request->validated('username'))) {
            return back()->withErrors(['username' => 'El usuario ya existe en este conjunto.'])->withInput();
        }

        StructureAppUser::query()->create([
            'client_id' => $clientId,
            'member_id' => $request->validated('member_id'),
            'username' => $request->validated('username'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('client.app-users.index')
            ->with('success', 'Usuario APP creado correctamente.');
    }
}
