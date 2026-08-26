<?php

declare(strict_types=1);

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\SecurityCompany;
use App\Services\Company\BuildSupervisionMapService;
use App\Services\Company\BuildSupervisionSummaryService;
use App\Services\Company\ExportSupervisionExecutiveReportService;
use App\Support\Platform\ActingCompanyResolver;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class SupervisionMapController extends Controller
{
    public function __construct(
        private readonly BuildSupervisionMapService $buildSupervisionMapService,
        private readonly BuildSupervisionSummaryService $buildSupervisionSummaryService,
        private readonly ExportSupervisionExecutiveReportService $exportReport,
    ) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()?->can('company.supervision.view'), 403);

        $companyId = app(ActingCompanyResolver::class)->requireId($request->user());
        $company = SecurityCompany::query()->findOrFail($companyId);

        $from = $request->string('from')->toString();
        $to = $request->string('to')->toString();
        $map = $this->buildSupervisionMapService->execute(
            $company,
            $from !== '' ? $from : null,
            $to !== '' ? $to : null,
        );
        $summary = $this->buildSupervisionSummaryService->execute(
            $company,
            $from !== '' ? $from : ($map['from'] ?? null),
            $to !== '' ? $to : ($map['to'] ?? null),
        );

        return view('modules.company.supervision.index', [
            'company' => $company,
            'map' => $map,
            'summary' => $summary,
            'tab' => $request->string('tab')->toString() ?: 'live',
        ]);
    }

    public function report(Request $request): BinaryFileResponse
    {
        abort_unless($request->user()?->can('company.supervision.view'), 403);

        $companyId = app(ActingCompanyResolver::class)->requireId($request->user());
        $company = SecurityCompany::query()->findOrFail($companyId);

        $from = $request->string('from')->toString();
        $to = $request->string('to')->toString();
        $snapshot = $this->buildSupervisionSummaryService->execute(
            $company,
            $from !== '' ? $from : null,
            $to !== '' ? $to : null,
        );
        $file = $this->exportReport->execute($snapshot);

        return response()
            ->download($file['path'], $file['filename'])
            ->deleteFileAfterSend();
    }
}
