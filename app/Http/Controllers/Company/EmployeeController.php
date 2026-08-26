<?php

declare(strict_types=1);

namespace App\Http\Controllers\Company;

use App\Domain\Employee\Data\SaveEmployeeData;
use App\Enums\BloodGroup;
use App\Enums\Sex;
use App\Exports\EmployeeImportTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Company\GrantEmployeeAccessRequest;
use App\Http\Requests\Company\PreviewEmployeeImportRequest;
use App\Http\Requests\Company\StoreEmployeeRequest;
use App\Http\Requests\Company\UpdateEmployeeRequest;
use App\Models\Client;
use App\Models\CompanyCollaboratorType;
use App\Models\CompanyJobTitle;
use App\Models\Employee;
use App\Models\IdentityDocumentType;
use App\Repositories\EmployeeRepository;
use App\Services\Company\CommitEmployeeImportService;
use App\Services\Company\GrantEmployeeAccessService;
use App\Services\Company\ManageEmployeeService;
use App\Services\Company\PreviewEmployeeImportService;
use App\Support\Auth\AssignableRoles;
use App\Support\Platform\ActingCompanyResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class EmployeeController extends Controller
{
    public function __construct(
        private readonly EmployeeRepository $employeeRepository,
        private readonly ManageEmployeeService $manageEmployeeService,
        private readonly GrantEmployeeAccessService $grantEmployeeAccessService,
        private readonly PreviewEmployeeImportService $previewEmployeeImportService,
        private readonly CommitEmployeeImportService $commitEmployeeImportService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Employee::class);
        $companyId = $this->companyId($request);
        $search = $request->string('q')->trim()->toString();
        $status = $request->string('status')->toString();
        if (! in_array($status, ['active', 'archived', 'all'], true)) {
            $status = 'active';
        }

        $employees = $this->employeeRepository->paginateForCompany(
            $companyId,
            15,
            $search !== '' ? $search : null,
            $status,
        );

        return view('modules.company.employees.index', compact('employees', 'search', 'status'));
    }

    public function downloadTemplate(Request $request): BinaryFileResponse
    {
        $this->authorize('create', Employee::class);

        return Excel::download(new EmployeeImportTemplateExport, 'formato-empleados-controla.xlsx');
    }

    public function storeImportPreview(PreviewEmployeeImportRequest $request): RedirectResponse
    {
        $companyId = $this->companyId($request);

        try {
            $preview = $request->file('file') !== null
                ? $this->previewEmployeeImportService->previewFile($request->file('file'), $companyId)
                : $this->previewEmployeeImportService->previewPaste((string) $request->input('paste'), $companyId);
        } catch (InvalidArgumentException $e) {
            return redirect()
                ->route('company.employees.index')
                ->with('error', $e->getMessage());
        }

        $this->previewEmployeeImportService->put($companyId, (int) $request->user()->id, $preview);

        return redirect()->route('company.employees.import.preview');
    }

    public function showImportPreview(Request $request): View|RedirectResponse
    {
        $this->authorize('create', Employee::class);
        $preview = $this->previewEmployeeImportService->get($this->companyId($request), (int) $request->user()->id);

        if ($preview === null) {
            return redirect()
                ->route('company.employees.index')
                ->with('error', 'No hay una revisión vigente. Vuelve a cargar el archivo.');
        }

        return view('modules.company.employees.import-preview', compact('preview'));
    }

    public function commitImport(Request $request): RedirectResponse
    {
        $this->authorize('create', Employee::class);
        try {
            $count = $this->commitEmployeeImportService->execute(
                $this->companyId($request),
                (int) $request->user()->id,
            );
        } catch (ValidationException $e) {
            return redirect()
                ->route('company.employees.import.preview')
                ->with('error', $e->validator->errors()->first() ?: 'No se pudo cargar.');
        }

        return redirect()
            ->route('company.employees.index')
            ->with('success', $count === 1 ? '1 empleado cargado.' : $count.' empleados cargados.');
    }

    public function cancelImport(Request $request): RedirectResponse
    {
        $this->authorize('create', Employee::class);
        $this->previewEmployeeImportService->forget($this->companyId($request), (int) $request->user()->id);

        return redirect()->route('company.employees.index');
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Employee::class);

        return view('modules.company.employees.create', $this->formPayload($this->companyId($request)));
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $companyId = $this->companyId($request);
        $employee = $this->manageEmployeeService->create(
            SaveEmployeeData::fromValidated($request->validated(), $companyId),
        );

        return redirect()
            ->route('company.employees.show', $employee)
            ->with('success', 'Empleado creado.');
    }

    public function show(Request $request, Employee $employee): View
    {
        $this->assertCompany($request, $employee);
        $this->authorize('view', $employee);
        $employee->load(['jobTitle', 'collaboratorType', 'user.roles', 'user.clients']);

        $companyId = $this->companyId($request);
        $clients = Client::query()
            ->where('security_company_id', $companyId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('modules.company.employees.show', [
            'employee' => $employee,
            'clients' => $clients,
            'roleOptions' => AssignableRoles::forEmployeeAccess(),
            'canGrantAccess' => $request->user()?->can('grantAccess', $employee) ?? false,
        ]);
    }

    public function edit(Request $request, Employee $employee): View
    {
        $this->assertCompany($request, $employee);
        $this->authorize('update', $employee);

        return view('modules.company.employees.edit', array_merge(
            $this->formPayload($this->companyId($request), $employee),
            ['employee' => $employee],
        ));
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $this->assertCompany($request, $employee);
        $this->manageEmployeeService->update(
            $employee,
            SaveEmployeeData::fromValidated($request->validated(), $this->companyId($request)),
        );

        return redirect()
            ->route('company.employees.show', $employee)
            ->with('success', 'Empleado actualizado.');
    }

    public function archive(Request $request, Employee $employee): RedirectResponse
    {
        $this->assertCompany($request, $employee);
        $this->authorize('archive', $employee);
        $this->manageEmployeeService->archive($employee);

        return redirect()
            ->route('company.employees.show', $employee)
            ->with('success', 'Empleado archivado.');
    }

    public function restore(Request $request, Employee $employee): RedirectResponse
    {
        $this->assertCompany($request, $employee);
        $this->authorize('restore', $employee);
        $this->manageEmployeeService->restore($employee);

        return redirect()
            ->route('company.employees.show', $employee)
            ->with('success', 'Empleado restaurado.');
    }

    public function grantAccess(GrantEmployeeAccessRequest $request, Employee $employee): RedirectResponse
    {
        $this->assertCompany($request, $employee);
        $user = $this->grantEmployeeAccessService->execute(
            $employee,
            $request->user(),
            $request->validated('role'),
            $request->validated('password'),
            array_map('intval', $request->input('client_ids', [])),
        );

        $message = 'Acceso creado.';
        if ($user->supervisor_code) {
            $message .= ' Código de revista: '.$user->supervisor_code;
        }

        return redirect()
            ->route('company.employees.show', $employee)
            ->with('success', $message);
    }

    /** @return array<string, mixed> */
    private function formPayload(int $companyId, ?Employee $employee = null): array
    {
        $jobTitles = CompanyJobTitle::query()
            ->where('security_company_id', $companyId)
            ->where(function ($query) use ($employee): void {
                $query->where('is_active', true);
                if ($employee !== null) {
                    $query->orWhereKey($employee->job_title_id);
                }
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        $collaboratorTypes = CompanyCollaboratorType::query()
            ->where('security_company_id', $companyId)
            ->where(function ($query) use ($employee): void {
                $query->where('is_active', true);
                if ($employee !== null) {
                    $query->orWhereKey($employee->collaborator_type_id);
                }
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        return [
            'jobTitles' => $jobTitles,
            'collaboratorTypes' => $collaboratorTypes,
            'documentTypes' => IdentityDocumentType::optionsForSelect(),
            'sexOptions' => Sex::options(),
            'bloodGroups' => BloodGroup::options(),
        ];
    }

    private function companyId(Request $request): int
    {
        return app(ActingCompanyResolver::class)->requireId($request->user());
    }

    private function assertCompany(Request $request, Employee $employee): void
    {
        abort_unless((int) $employee->security_company_id === $this->companyId($request), 404);
    }
}
