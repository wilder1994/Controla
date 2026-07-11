<?php

declare(strict_types=1);

namespace App\Http\Controllers\Client;

use App\Domain\Structure\Data\CreateMemberData;
use App\Enums\MemberType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\FinalizeMemberRequest;
use App\Http\Requests\Client\StoreMemberStep1Request;
use App\Http\Requests\Client\UpdateMemberRequest;
use App\Models\Location;
use App\Models\Structure;
use App\Models\StructureMember;
use App\Models\VisitorPreAuthorization;
use App\Repositories\StructureMemberRepository;
use App\Services\Structure\CreateMemberService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class MemberController extends Controller
{
    private const STEP1_SESSION_KEY = 'client.members.create.step1';

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

    public function storeStep1(StoreMemberStep1Request $request): RedirectResponse
    {
        $request->session()->put(self::STEP1_SESSION_KEY, $request->validated());

        return redirect()->route('client.members.create.confirm');
    }

    public function createConfirm(Request $request): View|RedirectResponse
    {
        $this->authorize('create', StructureMember::class);

        $step1 = $request->session()->get(self::STEP1_SESSION_KEY);

        if (! is_array($step1)) {
            return redirect()
                ->route('client.members.create')
                ->with('warning', 'Completa primero los datos básicos de la persona.');
        }

        $structure = Structure::query()->find($step1['structure_id']);
        $locations = Location::query()->where('is_active', true)->orderBy('name')->get();

        return view('modules.client.members.create-confirm', [
            'step1' => $step1,
            'structure' => $structure,
            'locations' => $locations,
        ]);
    }

    public function store(FinalizeMemberRequest $request): RedirectResponse
    {
        $step1 = $request->session()->get(self::STEP1_SESSION_KEY);

        if (! is_array($step1)) {
            return redirect()
                ->route('client.members.create')
                ->with('warning', 'La sesión del registro expiró. Vuelve a iniciar el wizard.');
        }

        $clientId = (int) $this->tenantContext->clientId();

        $member = $this->createMemberService->execute(new CreateMemberData(
            clientId: $clientId,
            structureId: (int) $step1['structure_id'],
            firstName: $step1['first_name'],
            lastName: $step1['last_name'],
            documentNumber: $step1['document_number'],
            memberType: MemberType::from($step1['member_type']),
            phonePrimary: $step1['phone_primary'] ?? null,
            phoneSecondary: null,
            email: $step1['email'] ?? null,
            hasAppAccess: $request->boolean('has_app_access'),
            isActive: $request->boolean('is_active', true),
        ));

        $locationIds = $request->validated('assigned_location_ids', []);
        if (count($locationIds) > 0) {
            $member->update([
                'metadata' => array_merge($member->metadata ?? [], [
                    'assigned_location_ids' => array_map('intval', $locationIds),
                ]),
            ]);
        }

        $request->session()->forget(self::STEP1_SESSION_KEY);

        return redirect()
            ->route('client.members.show', $member)
            ->with('success', 'Persona registrada en el censo.');
    }

    public function show(StructureMember $member): View
    {
        $this->authorize('view', $member);

        $member->load('structure');
        $locations = Location::query()->where('is_active', true)->orderBy('name')->get();
        $assignedLocationIds = $member->metadata['assigned_location_ids'] ?? [];

        return view('modules.client.members.show', compact('member', 'locations', 'assignedLocationIds'));
    }

    public function update(UpdateMemberRequest $request, StructureMember $member): RedirectResponse
    {
        $locationIds = $request->validated('assigned_location_ids', []);

        $member->update([
            'metadata' => array_merge($member->metadata ?? [], [
                'assigned_location_ids' => array_map('intval', $locationIds),
            ]),
        ]);

        return back()->with('success', 'Accesos portería actualizados.');
    }
}
