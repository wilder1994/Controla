<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Auth\ResolveUserHomeRoute;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class HomeController extends Controller
{
    public function __construct(
        private readonly ResolveUserHomeRoute $resolveUserHomeRoute,
    ) {}

    public function __invoke(Request $request): RedirectResponse
    {
        return redirect()->to($this->resolveUserHomeRoute->forUser($request->user()));
    }
}
