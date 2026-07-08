<?php

declare(strict_types=1);

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

final class CompanyLayout extends Component
{
    public function __construct(
        public ?string $title = null,
    ) {}

    public function render(): View|Closure|string
    {
        return view('layouts.company');
    }
}
