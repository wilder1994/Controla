<?php

declare(strict_types=1);

namespace App\Http\Controllers\Company;

use App\Domain\User\CreateUserData;
use App\Domain\User\UpdateUserData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Company\StoreUserRequest;
use App\Http\Requests\Company\UpdateUserRequest;
use App\Models\Client;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\User\ManageScopedUserService;
use App\Support\Auth\AssignableRoles;
use App\Support\Auth\UserManagementContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class UserController extends Controller
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly ManageScopedUserService $manageUserService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $search = $request->string('q')->trim()->toString();
        $users = $this->userRepository->paginateScoped(
            $request->user(),
            15,
            $search !== '' ? $search : null,
        );

        return view('modules.company.users.index', compact('users', 'search'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', User::class);

        $companyId = (int) $request->user()->security_company_id;
        $clients = Client::query()
            ->where('security_company_id', $companyId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('modules.company.users.create', [
            'roleOptions' => AssignableRoles::forCompany(),
            'clients' => $clients,
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = $this->manageUserService->create(
            new CreateUserData(
                name: $request->validated('name'),
                email: $request->validated('email'),
                password: $request->validated('password'),
                role: $request->validated('role'),
                securityCompanyId: (int) $request->user()->security_company_id,
                clientIds: array_map('intval', $request->input('client_ids', [])),
                isActive: $request->boolean('is_active', true),
            ),
            $request->user(),
            UserManagementContext::Company,
        );

        return redirect()
            ->route('company.users.edit', $user)
            ->with('success', 'Usuario creado correctamente.');
    }

    public function edit(Request $request, User $user): View
    {
        $this->authorize('update', $user);

        $user->load(['roles', 'clients']);
        $companyId = (int) $request->user()->security_company_id;
        $clients = Client::query()
            ->where('security_company_id', $companyId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('modules.company.users.edit', [
            'managedUser' => $user,
            'roleOptions' => AssignableRoles::forCompany(),
            'clients' => $clients,
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->manageUserService->update(
            $user,
            new UpdateUserData(
                name: $request->validated('name'),
                email: $request->validated('email'),
                password: $request->validated('password'),
                role: $request->validated('role'),
                clientIds: array_map('intval', $request->input('client_ids', [])),
                isActive: $request->boolean('is_active', true),
            ),
            $request->user(),
            UserManagementContext::Company,
        );

        return redirect()
            ->route('company.users.edit', $user)
            ->with('success', 'Usuario actualizado.');
    }
}
