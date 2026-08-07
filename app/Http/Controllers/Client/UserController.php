<?php

declare(strict_types=1);

namespace App\Http\Controllers\Client;

use App\Domain\User\CreateUserData;
use App\Domain\User\UpdateUserData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\StoreUserRequest;
use App\Http\Requests\Client\UpdateUserRequest;
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

        return view('modules.client.users.index', compact('users', 'search'));
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('modules.client.users.create', [
            'roleOptions' => AssignableRoles::forClient(),
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
                securityCompanyId: null,
                clientIds: [],
                isActive: $request->boolean('is_active', true),
                jobTitle: $request->validated('job_title'),
                avatarPath: UserAvatarUploader::store($request->file('avatar')),
            ),
            $request->user(),
            UserManagementContext::Client,
        );

        return redirect()
            ->route('client.users.edit', $user)
            ->with('success', 'Usuario creado correctamente.');
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        $user->load(['roles', 'clients']);

        return view('modules.client.users.edit', [
            'managedUser' => $user,
            'roleOptions' => AssignableRoles::forClient(),
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
                clientIds: null,
                isActive: $request->boolean('is_active', true),
                jobTitle: $request->validated('job_title'),
                avatarPath: UserAvatarUploader::store($request->file('avatar')),
            ),
            $request->user(),
            UserManagementContext::Client,
        );

        return redirect()
            ->route('client.users.edit', $user)
            ->with('success', 'Usuario actualizado.');
    }
}
