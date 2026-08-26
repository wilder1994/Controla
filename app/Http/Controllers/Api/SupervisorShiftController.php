<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\User;
use App\Services\Company\ManageSupervisorShiftService;
use App\Services\Company\RecordSupervisorProReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class SupervisorShiftController extends Controller
{
    public function __construct(
        private readonly ManageSupervisorShiftService $shiftService,
        private readonly RecordSupervisorProReviewService $reviewService,
    ) {}

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'nullable|string|max:100',
        ]);

        $user = User::query()->where('email', $request->string('email'))->with('securityCompany')->first();

        if (! $user || ! Hash::check((string) $request->input('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Credenciales inválidas.'],
            ]);
        }

        if (! $user->hasRole('supervisor') || $user->security_company_id === null) {
            abort(403, 'Esta cuenta no es de supervisor.');
        }

        $company = $user->securityCompany;
        if ($company === null || ! $company->hasSupervisionPackage()) {
            abort(403, 'La empresa no tiene Supervisión Pro.');
        }

        $token = $user->createToken($request->string('device_name')->toString() ?: 'supervision-pro')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'company_id' => $user->security_company_id,
            ],
        ]);
    }

    public function current(Request $request): JsonResponse
    {
        $shift = $this->shiftService->currentFor($request->user());

        return response()->json(['shift' => $shift]);
    }

    public function sites(Request $request): JsonResponse
    {
        $sites = Client::query()
            ->where('security_company_id', $request->user()->security_company_id)
            ->where('has_supervision', true)
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'address', 'has_access']);

        return response()->json(['sites' => $sites]);
    }

    public function open(Request $request): JsonResponse
    {
        $data = $request->validate([
            'km_start' => ['nullable', 'integer', 'min:0'],
        ]);

        $shift = $this->shiftService->open($request->user(), $data['km_start'] ?? null);

        return response()->json(['shift' => $shift], 201);
    }

    public function ping(Request $request): JsonResponse
    {
        $data = $request->validate([
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
            'accuracy' => ['nullable', 'numeric'],
        ]);

        $shift = $this->shiftService->currentFor($request->user());
        abort_if($shift === null, 422, 'No hay turno abierto.');

        $point = $this->shiftService->ping(
            $shift,
            (float) $data['latitude'],
            (float) $data['longitude'],
            isset($data['accuracy']) ? (float) $data['accuracy'] : null,
        );

        return response()->json(['location' => $point]);
    }

    public function close(Request $request): JsonResponse
    {
        $data = $request->validate([
            'km_end' => ['nullable', 'integer', 'min:0'],
        ]);

        $shift = $this->shiftService->currentFor($request->user());
        abort_if($shift === null, 422, 'No hay turno abierto.');

        $closed = $this->shiftService->close($shift, $data['km_end'] ?? null);

        return response()->json(['shift' => $closed]);
    }

    public function review(Request $request): JsonResponse
    {
        $data = $request->validate([
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ]);

        $shift = $this->shiftService->currentFor($request->user());
        abort_if($shift === null, 422, 'No hay turno abierto.');

        $client = Client::query()->findOrFail($data['client_id']);
        $review = $this->reviewService->execute(
            $shift,
            $client,
            (string) ($data['notes'] ?? ''),
            isset($data['latitude']) ? (float) $data['latitude'] : null,
            isset($data['longitude']) ? (float) $data['longitude'] : null,
        );

        return response()->json(['review' => $review], 201);
    }
}
