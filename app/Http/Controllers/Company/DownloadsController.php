<?php

declare(strict_types=1);

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Support\Supervision\SupervisionAppUrl;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class DownloadsController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->can('company.dashboard'), 403);

        return view('modules.company.downloads.index', [
            'pwaUrl' => SupervisionAppUrl::pwa(),
        ]);
    }
}
