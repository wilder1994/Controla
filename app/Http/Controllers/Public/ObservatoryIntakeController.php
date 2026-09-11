<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\InstallationKind;
use App\Enums\ObservatoryReportKind;
use App\Http\Controllers\Controller;
use App\Http\Requests\Observatory\StorePublicObservatoryReportRequest;
use App\Models\Client;
use App\Models\Installation;
use App\Models\ObservatoryReport;
use App\Services\Observatory\SubmitObservatoryReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class ObservatoryIntakeController extends Controller
{
    public function __construct(
        private readonly SubmitObservatoryReportService $submit,
    ) {}

    public function show(string $slug): View
    {
        $row = $this->client($slug);

        return view('modules.observatory.public.intake', [
            'client' => $row,
            'kinds' => ObservatoryReportKind::options(),
        ]);
    }

    public function sites(Request $request, string $slug): JsonResponse
    {
        $row = $this->client($slug);
        $search = $request->string('q')->trim()->toString();

        $sites = Installation::query()
            ->withoutGlobalScopes()
            ->where('client_id', $row->id)
            ->where('is_active', true)
            ->where('kind', InstallationKind::Colegio->value)
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', '%'.$search.'%')
                        ->orWhere('dane_code', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'dane_code', 'city']);

        return response()->json([
            'sites' => $sites->map(static fn (Installation $site): array => [
                'id' => (int) $site->id,
                'name' => $site->name,
                'dane_code' => $site->dane_code,
                'city' => $site->city,
            ])->all(),
        ]);
    }

    public function store(StorePublicObservatoryReportRequest $request, string $slug): RedirectResponse
    {
        $row = $this->client($slug);

        try {
            $report = $this->submit->execute($row, [
                'installation_id' => (int) $request->validated('installation_id'),
                'kind' => (string) $request->validated('kind'),
                'body' => (string) $request->validated('body'),
                'is_anonymous' => $request->boolean('is_anonymous'),
                'reporter_name' => $request->validated('reporter_name'),
                'reporter_phone' => $request->validated('reporter_phone'),
                'photo' => $request->file('photo'),
            ], $request->ip());
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }

        return redirect()
            ->route('observatory.public.thanks', [$row->slug, $report]);
    }

    public function thanks(string $slug, ObservatoryReport $report): View
    {
        $row = $this->client($slug);
        abort_unless((int) $report->client_id === (int) $row->id, 404);
        $report->load('event.installation');

        return view('modules.observatory.public.thanks', [
            'client' => $row,
            'report' => $report,
        ]);
    }

    private function client(string $slug): Client
    {
        return Client::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();
    }
}
