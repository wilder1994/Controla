<?php

declare(strict_types=1);

namespace App\Http\Controllers\Client;

use App\Domain\Structure\Data\CreateMemberData;
use App\Enums\MemberType;
use App\Exports\MembersAssemblyExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\StoreMemberRequest;
use App\Models\Structure;
use App\Models\StructureMember;
use App\Repositories\StructureMemberRepository;
use App\Services\Structure\CreateMemberService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class MemberController extends Controller
{
    public function __construct(
        private readonly StructureMemberRepository $memberRepository,
        private readonly CreateMemberService $createMemberService,
        private readonly TenantContext $tenantContext,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', StructureMember::class);

        $clientId = (int) $this->tenantContext->clientId();
        $members = $this->memberRepository->paginateForClient(
            $clientId,
            $request->string('q')->toString() ?: null,
            $request->integer('structure_id') ?: null,
        );
        $structures = Structure::query()->with(['installation', 'parent'])->orderBy('name')->get();
        $memberTypes = MemberType::options();

        return view('modules.client.members.index', compact('members', 'structures', 'memberTypes'));
    }

    public function create(): View
    {
        $this->authorize('create', StructureMember::class);

        $structures = Structure::query()->with(['installation', 'parent'])->orderBy('name')->get();
        $memberTypes = MemberType::options();

        return view('modules.client.members.create', compact('structures', 'memberTypes'));
    }

    public function store(StoreMemberRequest $request): RedirectResponse
    {
        $clientId = (int) $this->tenantContext->clientId();

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('members', 'public');
        }

        $member = $this->createMemberService->execute(new CreateMemberData(
            clientId: $clientId,
            structureId: (int) $request->validated('structure_id'),
            firstName: $request->validated('first_name'),
            lastName: $request->validated('last_name'),
            documentNumber: $request->validated('document_number'),
            memberType: MemberType::from($request->validated('member_type')),
            phonePrimary: $request->validated('phone_primary'),
            phoneSecondary: $request->validated('phone_secondary'),
            email: $request->validated('email'),
            hasAppAccess: $request->boolean('has_app_access'),
            isActive: $request->boolean('is_active', true),
            photoPath: $photoPath,
        ));

        return redirect()
            ->route('client.members.show', $member)
            ->with('success', 'Persona registrada en el censo.');
    }

    public function show(StructureMember $member): View
    {
        $this->authorize('view', $member);

        $member->load(['structure.installation', 'structure.parent', 'appUser']);

        return view('modules.client.members.show', compact('member'));
    }

    public function edit(StructureMember $member): View
    {
        $this->authorize('update', $member);

        $structures = Structure::query()->with(['installation', 'parent'])->orderBy('name')->get();
        $memberTypes = MemberType::options();

        return view('modules.client.members.edit', compact('member', 'structures', 'memberTypes'));
    }

    public function update(StoreMemberRequest $request, StructureMember $member): RedirectResponse
    {
        $this->authorize('update', $member);

        $data = [
            'structure_id' => (int) $request->validated('structure_id'),
            'first_name' => $request->validated('first_name'),
            'last_name' => $request->validated('last_name'),
            'document_number' => $request->validated('document_number'),
            'member_type' => MemberType::from($request->validated('member_type')),
            'phone_primary' => $request->validated('phone_primary'),
            'phone_secondary' => $request->validated('phone_secondary'),
            'email' => $request->validated('email'),
            'has_app_access' => $request->boolean('has_app_access'),
            'is_active' => $request->boolean('is_active', true),
        ];

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('members', 'public');
        }

        $member->update($data);

        return redirect()
            ->route('client.members.show', $member)
            ->with('success', 'Persona actualizada en el censo.');
    }

    public function export(): BinaryFileResponse
    {
        $this->authorize('viewAny', StructureMember::class);

        $clientId = (int) $this->tenantContext->clientId();

        return Excel::download(
            new MembersAssemblyExport($clientId),
            'listado-miembros-asamblea.xlsx',
        );
    }
}
