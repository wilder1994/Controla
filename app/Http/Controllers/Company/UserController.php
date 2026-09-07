<?php

declare(strict_types=1);

namespace App\Http\Controllers\Company;

use App\Domain\User\UpdateUserData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Company\StoreUserRequest;
use App\Http\Requests\Company\UpdateUserRequest;
use App\Models\Client;
use App\Models\CompanyJobTitle;
use App\Models\Employee;
use App\Models\User;
use App\Repositories\EmployeeRepository;
use App\Repositories\UserRepository;
use App\Services\Company\GrantEmployeeAccessService;
use App\Services\User\ManageScopedUserService;
use App\Support\Auth\AssignableRoles;
use App\Support\Auth\UserManagementContext;
use App\Support\Platform\ActingCompanyResolver;
use App\Support\User\UserAvatarUploader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class UserController extends Controller
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EmployeeRepository $employeeRepository,
        private readonly ManageScopedUserService $manageUserService,
        private readonly GrantEmployeeAccessService $grantEmployeeAccessService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $search = $request->string('q')->trim()->toString();
        $status = $request->string('status')->toString();
        if (! in_array($status, ['active', 'inactive'], true)) {
            $status = 'active';
        }

        $users = $this->userRepository->paginateScoped(
            $request->user(),
            15,
            $search !== '' ? $search : null,
            $status,
        );

        return view('modules.company.users.index', compact('users', 'search', 'status'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', User::class);

        $companyId = app(ActingCompanyResolver::class)->requireId($request->user());
        $clients = Client::query()
            ->where('security_company_id', $companyId)
            ->orderBy('name')
            ->get(['id', 'name']);
        $jobTitles = CompanyJobTitle::query()
            ->where('security_company_id', $companyId)
            ->active()
            ->get(['id', 'name']);

        return view('modules.company.users.create', [
            'roleOptions' => AssignableRoles::forCompany(),
            'clients' => $clients,
            'jobTitles' => $jobTitles,
        ]);
    }

    public function searchEmployees(Request $request): JsonResponse
    {
        $this->authorize('create', User::class);
        $companyId = app(ActingCompanyResolver::class)->requireId($request->user());

        return response()->json([
            'employees' => $this->employeeRepository->searchWithoutUser(
                $companyId,
                $request->string('q')->toString(),
            ),
        ]);
    }

    public function previewCredentials(Request $request): JsonResponse
    {
        $this->authorize('create', User::class);
        $companyId = app(ActingCompanyResolver::class)->requireId($request->user());
        $employee = Employee::query()->findOrFail((int) $request->input('employee_id'));
        abort_unless((int) $employee->security_company_id === $companyId, 404);

        return response()->json($this->grantEmployeeAccessService->previewCredentials($employee));
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $password = $request->validated('password');
        $employee = Employee::query()->findOrFail((int) $request->validated('employee_id'));
        $user = $this->grantEmployeeAccessService->execute(
            $employee,
            $request->user(),
            $request->validated('role'),
            $password,
            array_map('intval', $request->input('client_ids', [])),
            $request->validated('username'),
            $request->validated('job_title'),
        );

        if ($request->hasFile('avatar')) {
            $path = UserAvatarUploader::store($request->file('avatar'));
            if ($path !== null) {
                $user->update(['avatar_path' => $path]);
            }
        }

        $message = 'Usuario creado.';
        if ($user->supervisor_code) {
            $message .= ' Código de 6 dígitos (revista Accesos): '.$user->supervisor_code;
        }

        return redirect()
            ->route('company.users.edit', $user)
            ->with([
                'success' => $message,
                'issued_login' => $user->username,
                'issued_password' => $password,
            ]);
    }

    public function edit(Request $request, User $user): View
    {
        $this->authorize('update', $user);

        $user->load(['roles', 'clients', 'employee.jobTitle']);
        $companyId = app(ActingCompanyResolver::class)->requireId($request->user());
        $clients = Client::query()
            ->where('security_company_id', $companyId)
            ->orderBy('name')
            ->get(['id', 'name']);
        $jobTitles = CompanyJobTitle::query()
            ->where('security_company_id', $companyId)
            ->active()
            ->get(['id', 'name']);

        return view('modules.company.users.edit', [
            'managedUser' => $user,
            'roleOptions' => AssignableRoles::forCompany(),
            'clients' => $clients,
            'jobTitles' => $jobTitles,
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->manageUserService->update(
            $user,
            new UpdateUserData(
                name: $user->employee?->fullName() ?: $request->validated('name'),
                email: $user->email,
                password: $request->validated('password'),
                role: $request->validated('role'),
                clientIds: array_map('intval', $request->input('client_ids', [])),
                isActive: $request->boolean('is_active', true),
                jobTitle: $request->validated('job_title'),
                avatarPath: UserAvatarUploader::store($request->file('avatar')),
                regenerateSupervisorCode: $request->boolean('regenerate_supervisor_code'),
            ),
            $request->user(),
            UserManagementContext::Company,
        );

        return redirect()
            ->route('company.users.edit', $user)
            ->with('success', 'Usuario actualizado.');
    }

    public function deactivate(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);
        $this->manageUserService->setActive($user, $request->user(), false);

        return redirect()
            ->route('company.users.index', ['status' => 'inactive'])
            ->with('success', 'Usuario desactivado. El historial se conserva.');
    }

    public function reactivate(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);
        $this->manageUserService->setActive($user, $request->user(), true);

        return redirect()
            ->route('company.users.index', ['status' => 'active'])
            ->with('success', 'Usuario reactivado.');
    }
}
