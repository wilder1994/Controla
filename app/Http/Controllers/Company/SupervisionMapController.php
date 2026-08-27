<?php

declare(strict_types=1);

namespace App\Http\Controllers\Company;

use App\Domain\Supervision\Data\SupervisionQueryFilter;
use App\Http\Controllers\Controller;
use App\Models\SecurityCompany;
use App\Models\SupervisorZone;
use App\Models\User;
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
        $filter = $this->queryFilter($request, $company);

        $map = $this->buildSupervisionMapService->execute($company, $filter);
        $summary = $this->buildSupervisionSummaryService->execute(
            $company,
            $filter->withDates(
                $filter->from ?? ($map['from'] ?? null),
                $filter->to ?? ($map['to'] ?? null),
            ),
        );

        $tab = $request->string('tab')->toString();
        if (! in_array($tab, ['live', 'history', 'summary'], true)) {
            $tab = 'live';
        }

        return view('modules.company.supervision.index', [
            'company' => $company,
            'map' => $map,
            'summary' => $summary,
            'tab' => $tab,
            'filter' => $filter,
            'zones' => SupervisorZone::query()
                ->where('security_company_id', $companyId)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'is_active']),
            'supervisors' => User::query()
                ->where('security_company_id', $companyId)
                ->role('supervisor')
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function report(Request $request): BinaryFileResponse
    {
        abort_unless($request->user()?->can('company.supervision.view'), 403);

        $companyId = app(ActingCompanyResolver::class)->requireId($request->user());
        $company = SecurityCompany::query()->findOrFail($companyId);
        $filter = $this->queryFilter($request, $company);

        $snapshot = $this->buildSupervisionSummaryService->execute($company, $filter);
        $file = $this->exportReport->execute($snapshot);

        return response()
            ->download($file['path'], $file['filename'])
            ->deleteFileAfterSend();
    }

    private function queryFilter(Request $request, SecurityCompany $company): SupervisionQueryFilter
    {
        $from = $request->string('from')->toString();
        $to = $request->string('to')->toString();
        $zoneId = $request->integer('zone_id');
        $supervisorId = $request->integer('supervisor_id');

        if ($zoneId > 0) {
            $owned = SupervisorZone::query()
                ->where('security_company_id', $company->id)
                ->whereKey($zoneId)
                ->exists();
            $zoneId = $owned ? $zoneId : 0;
        }

        if ($supervisorId > 0) {
            $owned = User::query()
                ->where('security_company_id', $company->id)
                ->role('supervisor')
                ->whereKey($supervisorId)
                ->exists();
            $supervisorId = $owned ? $supervisorId : 0;
        }

        return new SupervisionQueryFilter(
            from: $from !== '' ? $from : null,
            to: $to !== '' ? $to : null,
            zoneId: $zoneId > 0 ? $zoneId : null,
            supervisorId: $supervisorId > 0 ? $supervisorId : null,
        );
    }
}
