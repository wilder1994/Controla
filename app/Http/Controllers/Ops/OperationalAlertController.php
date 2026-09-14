<?php

declare(strict_types=1);

namespace App\Http\Controllers\Ops;

use App\Http\Controllers\Controller;
use App\Services\Ops\ResolveLiveOperationalAlertsService;
use App\Services\Ops\TriggerPanicService;
use App\Support\Platform\ActingCompanyResolver;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class OperationalAlertController extends Controller
{
    public function __construct(
        private readonly ResolveLiveOperationalAlertsService $live,
        private readonly TriggerPanicService $panic,
        private readonly ActingCompanyResolver $actingCompany,
        private readonly TenantContext $tenantContext,
    ) {}

    public function poll(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_if($user === null, 403);

        $after = (int) $request->query('after', 0);

        return response()->json([
            'alerts' => $this->live->pending($user, $after),
        ]);
    }

    public function panic(Request $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        abort_if($user === null, 403);

        $data = $request->validate([
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'note' => ['nullable', 'string', 'max:240'],
        ]);

        $lat = isset($data['latitude']) ? (float) $data['latitude'] : null;
        $lng = isset($data['longitude']) ? (float) $data['longitude'] : null;
        $note = (string) ($data['note'] ?? '');

        if ($user->hasAnyRole(['company-admin', 'colaborador']) && $this->actingCompany->id($user) !== null) {
            $this->panic->execute($user, $lat, $lng, $note);
        } else {
            $this->panic->fromClientPanel($user, $this->tenantContext, $lat, $lng, $note);
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => true], 201);
        }

        return back()->with('success', 'Pánico registrado. El personal de la empresa fue notificado.');
    }
}
