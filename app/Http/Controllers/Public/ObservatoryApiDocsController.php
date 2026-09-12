<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\Observatory\BuildObservatoryOpenApiSpec;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

final class ObservatoryApiDocsController extends Controller
{
    public function page(): View
    {
        return view('modules.observatory.public.docs', [
            'specUrl' => url('/api/observatory/openapi.json'),
        ]);
    }

    public function spec(BuildObservatoryOpenApiSpec $spec): JsonResponse
    {
        return response()->json($spec->execute())
            ->header('Access-Control-Allow-Origin', '*');
    }
}
