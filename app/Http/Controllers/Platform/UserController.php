<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Domain\User\CreateUserData;
use App\Domain\User\UpdateUserData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StoreUserRequest;
use App\Http\Requests\Platform\UpdateUserRequest;
use App\Models\Client;
use App\Models\SecurityCompany;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\User\ManageScopedUserService;
use App\Support\Auth\AssignableRoles;
use App\Support\Auth\UserManagementContext;
use App\Support\User\UserAvatarUploader;
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

        return view('modules.admin.users.index', compact('users', 'search'));
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('modules.admin.users.create', [
            'roleOptions' => AssignableRoles::forPlatform(),
            'companies' => SecurityCompany::query()->orderBy('trade_name')->get(['id', 'trade_name', 'legal_name']),
            'clients' => Client::query()->with('securityCompany')->orderBy('name')->get(),
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
                securityCompanyId: $request->filled('security_company_id')
                    ? (int) $request->validated('security_company_id')
                    : null,
                clientIds: array_map('intval', $request->input('client_ids', [])),
                isActive: $request->boolean('is_active', true),
                jobTitle: $request->validated('job_title'),
                avatarPath: UserAvatarUploader::store($request->file('avatar')),
            ),
            $request->user(),
            UserManagementContext::Platform,
        );

        $message = 'Usuario creado correctamente.';
        if ($user->supervisor_code) {
            $message .= ' Código de revista: '.$user->supervisor_code;
        }

        return redirect()
            ->route('admin.users.edit', $user)
            ->with('success', $message);
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        $user->load(['roles', 'clients', 'securityCompany']);

        return view('modules.admin.users.edit', [
            'managedUser' => $user,
            'roleOptions' => AssignableRoles::forPlatform(),
            'companies' => SecurityCompany::query()->orderBy('trade_name')->get(['id', 'trade_name', 'legal_name']),
            'clients' => Client::query()->with('securityCompany')->orderBy('name')->get(),
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
                jobTitle: $request->validated('job_title'),
                avatarPath: UserAvatarUploader::store($request->file('avatar')),
                regenerateSupervisorCode: $request->boolean('regenerate_supervisor_code'),
            ),
            $request->user(),
            UserManagementContext::Platform,
        );

        return redirect()
            ->route('admin.users.edit', $user)
            ->with('success', 'Usuario actualizado.');
    }
}
