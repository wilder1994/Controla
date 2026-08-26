<?php

declare(strict_types=1);

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\SecurityCompany;
use App\Services\Company\BuildSupervisionMapService;
use App\Support\Platform\ActingCompanyResolver;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class SupervisionMapController extends Controller
{
    public function __construct(
        private readonly BuildSupervisionMapService $buildSupervisionMapService,
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

        return view('modules.company.supervision.index', [
            'company' => $company,
            'map' => $map,
        ]);
    }
}
