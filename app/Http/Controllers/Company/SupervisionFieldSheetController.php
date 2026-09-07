<?php

declare(strict_types=1);

namespace App\Http\Controllers\Company;

use App\Enums\SupervisorFieldSheetKind;
use App\Http\Controllers\Controller;
use App\Services\Company\BuildSupervisorFieldSheetService;
use App\Support\Platform\ActingCompanyResolver;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class SupervisionFieldSheetController extends Controller
{
    public function __construct(
        private readonly BuildSupervisorFieldSheetService $sheets,
    ) {}

    public function show(Request $request, string $kind, int $id): View
    {
        abort_unless($request->user()?->can('company.supervision.view'), 403);

        $sheetKind = SupervisorFieldSheetKind::tryFrom($kind);
        abort_if($sheetKind === null, 404);

        $companyId = app(ActingCompanyResolver::class)->requireId($request->user());
        $sheet = $this->sheets->forCompany($companyId, $sheetKind, $id);
        abort_if($sheet === null, 404);

        return view('modules.company.supervision.ficha', ['sheet' => $sheet]);
    }
}
