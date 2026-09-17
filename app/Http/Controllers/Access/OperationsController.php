<?php

namespace App\Http\Controllers\Access;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class OperationsController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('access.dashboard');
    }
}
