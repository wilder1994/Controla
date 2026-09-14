<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;

final class FieldAppController extends Controller
{
    /** @var array<string, string> */
    private const FILES = [
        'index.html' => 'text/html; charset=UTF-8',
        'app.js' => 'application/javascript; charset=UTF-8',
        'offline.js' => 'application/javascript; charset=UTF-8',
        'manifest.json' => 'application/manifest+json',
        'sw.js' => 'application/javascript; charset=UTF-8',
    ];

    public function __invoke(?string $file = null): Response
    {
        $name = $file ?: 'index.html';
        $type = self::FILES[$name] ?? null;
        if ($type === null) {
            abort(404);
        }

        $path = public_path('campo/'.$name);
        if (! is_file($path)) {
            abort(404);
        }

        return response(file_get_contents($path), 200, [
            'Content-Type' => $type,
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }
}
