<?php

declare(strict_types=1);

namespace App\Http\Controllers\Company;

use App\Enums\PanicAttentionStatus;
use App\Http\Controllers\Controller;
use App\Models\PanicAttention;
use App\Services\Ops\ClaimPanicAttentionService;
use App\Services\Ops\ClosePanicAttentionService;
use App\Support\Platform\ActingCompanyResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Throwable;

final class PanicAttentionController extends Controller
{
    public function __construct(
        private readonly ClaimPanicAttentionService $claim,
        private readonly ClosePanicAttentionService $close,
        private readonly ActingCompanyResolver $actingCompany,
    ) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()?->can('ops.panic.attend'), 403);

        $companyId = $this->actingCompany->requireId($request->user());
        $status = PanicAttentionStatus::tryFrom((string) $request->query('status', ''));

        $rows = PanicAttention::query()
            ->where('security_company_id', $companyId)
            ->when($status, fn ($q) => $q->where('status', $status->value))
            ->with(['alert.actor', 'alert.client', 'alert.installation', 'attendee'])
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('modules.company.panics.index', [
            'attentions' => $rows,
            'status' => $status?->value ?? '',
        ]);
    }

    public function show(Request $request, PanicAttention $panic): View
    {
        $this->assertCompany($request, $panic);

        $panic->load(['alert.actor', 'alert.client', 'alert.installation', 'attendee']);

        return view('modules.company.panics.show', [
            'attention' => $panic,
            'mapsKey' => config('google-maps.api_key'),
        ]);
    }

    public function print(Request $request, PanicAttention $panic): View
    {
        $this->assertCompany($request, $panic);
        $panic->load(['alert.actor', 'alert.client', 'alert.installation', 'attendee']);

        $company = \App\Models\SecurityCompany::query()->find($panic->security_company_id);

        return view('modules.company.panics.ficha', [
            'attention' => $panic,
            'company' => $company,
            'companyLogoSrc' => $this->logoDataUri($company?->logo_path),
        ]);
    }

    public function claim(Request $request): JsonResponse|RedirectResponse
    {
        abort_unless($request->user()?->can('ops.panic.attend'), 403);

        $data = $request->validate([
            'alert_id' => ['required', 'integer', 'exists:operational_alerts,id'],
        ]);

        try {
            $attention = $this->claim->execute($request->user(), (int) $data['alert_id']);
        } catch (AccessDeniedHttpException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 403);
            }

            abort(403, $e->getMessage());
        } catch (ConflictHttpException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 409);
            }

            return back()->with('error', $e->getMessage());
        } catch (Throwable $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->with('error', $e->getMessage());
        }

        $url = route('company.panics.show', $attention);
        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'url' => $url]);
        }

        return redirect($url);
    }

    public function update(Request $request, PanicAttention $panic): RedirectResponse
    {
        $this->assertCompany($request, $panic);

        $data = $request->validate([
            'observations' => ['required', 'string', 'max:4000'],
            'close' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('close')) {
            $this->close->execute($panic, $request->user(), $data['observations']);

            return redirect()
                ->route('company.panics.show', $panic)
                ->with('success', 'Ficha de pánico cerrada.');
        }

        $this->close->saveNotes($panic, $request->user(), $data['observations']);

        return back()->with('success', 'Observaciones guardadas.');
    }

    private function assertCompany(Request $request, PanicAttention $panic): void
    {
        abort_unless($request->user()?->can('ops.panic.attend'), 403);
        $companyId = $this->actingCompany->requireId($request->user());
        abort_unless((int) $panic->security_company_id === $companyId, 404);
    }

    private function logoDataUri(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }
        if (! \Illuminate\Support\Facades\Storage::disk('local')->exists($path)) {
            return null;
        }
        $bytes = \Illuminate\Support\Facades\Storage::disk('local')->get($path);
        if ($bytes === null || $bytes === '') {
            return null;
        }
        $mime = \Illuminate\Support\Facades\Storage::disk('local')->mimeType($path) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode($bytes);
    }
}
