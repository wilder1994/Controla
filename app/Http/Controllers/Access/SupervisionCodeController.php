<?php

namespace App\Http\Controllers\Access;

use App\Http\Controllers\Controller;
use App\Models\SupervisionCode;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupervisionCodeController extends Controller
{
    public function index()
    {
        $codes = SupervisionCode::withCount('supervisions')->latest()->paginate(20);

        return view('modules.access.supervision.codes.index', compact('codes'));
    }

    public function store(Request $request)
    {
        $clientId = app(TenantContext::class)->clientId();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('supervision_codes')
                    ->where('client_id', $clientId)
                    ->whereNull('deleted_at'),
            ],
        ]);

        SupervisionCode::create([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'is_active' => true,
        ]);

        return back()->with('success', 'Código de supervisor "'.$validated['name'].'" creado.');
    }

    public function toggle(SupervisionCode $code)
    {
        $code->update(['is_active' => ! $code->is_active]);

        return back()->with('success', $code->is_active
            ? 'Código de "'.$code->name.'" activado.'
            : 'Código de "'.$code->name.'" desactivado.');
    }

    public function destroy(SupervisionCode $code)
    {
        $code->delete();

        return back()->with('success', 'Código de supervisor eliminado.');
    }
}
