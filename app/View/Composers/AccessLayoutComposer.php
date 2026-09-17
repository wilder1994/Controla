<?php

declare(strict_types=1);

namespace App\View\Composers;

use App\Services\Access\PorteriaDoorService;
use Illuminate\View\View;

final class AccessLayoutComposer
{
    public function __construct(
        private readonly PorteriaDoorService $doors,
    ) {}

    public function compose(View $view): void
    {
        $request = request();
        $door = $this->doors->current($request) ?? $this->doors->bindSingleIfOnlyOne($request);

        $view->with('operatingDoor', $door);
    }
}
