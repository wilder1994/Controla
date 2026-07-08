<?php

declare(strict_types=1);

namespace App\Http\Controllers\Client;

use App\Domain\Structure\Data\CreateMemberData;
use App\Enums\MemberType;
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
        $structures = Structure::query()->orderBy('name')->get();
        $memberTypes = MemberType::options();

        return view('modules.client.members.index', compact('members', 'structures', 'memberTypes'));
    }

    public function create(): View
    {
        $this->authorize('create', StructureMember::class);

        $structures = Structure::query()->orderBy('name')->get();
        $memberTypes = MemberType::options();

        return view('modules.client.members.create', compact('structures', 'memberTypes'));
    }

    public function store(StoreMemberRequest $request): RedirectResponse
    {
        $clientId = (int) $this->tenantContext->clientId();

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
        ));

        return redirect()
            ->route('client.members.show', $member)
            ->with('success', 'Persona registrada en el censo.');
    }

    public function show(StructureMember $member): View
    {
        $this->authorize('view', $member);

        $member->load('structure');

        return view('modules.client.members.show', compact('member'));
    }
}
