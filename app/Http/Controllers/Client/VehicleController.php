<?php

declare(strict_types=1);

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\StoreStructureVehicleRequest;
use App\Models\Structure;
use App\Models\Vehicle;
use App\Repositories\StructureVehicleRepository;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class VehicleController extends Controller
{
    public function __construct(
        private readonly StructureVehicleRepository $vehicleRepository,
        private readonly TenantContext $tenantContext,
    ) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()?->can('client.vehicles.manage'), 403);

        $clientId = (int) $this->tenantContext->clientId();
        $vehicles = $this->vehicleRepository->paginateForClient(
            $clientId,
            $request->string('q')->toString() ?: null,
            $request->integer('structure_id') ?: null,
        );
        $structures = Structure::query()->orderBy('name')->get();

        return view('modules.client.vehicles.index', compact('vehicles', 'structures'));
    }

    public function create(): View
    {
        abort_unless(auth()->user()?->can('client.vehicles.manage'), 403);

        $structures = Structure::query()->orderBy('name')->get();

        return view('modules.client.vehicles.create', compact('structures'));
    }

    public function store(StoreStructureVehicleRequest $request): RedirectResponse
    {
        $clientId = (int) $this->tenantContext->clientId();

        Vehicle::query()->create([
            'client_id' => $clientId,
            'structure_id' => $request->validated('structure_id'),
            'plate' => strtoupper($request->validated('plate')),
            'brand' => $request->validated('brand'),
            'model' => $request->validated('model'),
            'color' => $request->validated('color'),
            'type' => $request->validated('type', 'carro'),
            'assigned_parking_spot' => $request->validated('assigned_parking_spot'),
            'soat_expires_at' => $request->validated('soat_expires_at'),
            'license_expires_at' => $request->validated('license_expires_at'),
            'is_visitor_vehicle' => $request->boolean('is_visitor_vehicle'),
        ]);

        return redirect()
            ->route('client.vehicles.index')
            ->with('success', 'Vehículo registrado en el censo.');
    }
}
