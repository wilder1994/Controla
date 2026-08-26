<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PreAuthorization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class PreAuthorizationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $preAuthorizations = PreAuthorization::with(['visitor', 'location'])
            ->where('host_id', $request->user()->id)
            ->latest('scheduled_date')
            ->paginate(20);

        return response()->json($preAuthorizations);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'visitor_id' => 'required|exists:visitors,id',
            'location_id' => 'required|exists:locations,id',
            'scheduled_date' => 'required|date|after_or_equal:today',
            'scheduled_time' => 'nullable|date_format:H:i',
            'notes' => 'nullable|string|max:500',
        ]);

        $validated['host_id'] = $request->user()->id;
        $validated['status'] = 'pending';
        $validated['qr_code'] = Str::random(32);

        $preAuth = PreAuthorization::create($validated);

        return response()->json($preAuth->load('visitor', 'location'), 201);
    }

    public function show(PreAuthorization $preAuthorization, Request $request): JsonResponse
    {
        if ($preAuthorization->host_id !== $request->user()->id) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        return response()->json($preAuthorization->load('visitor', 'location'));
    }

    public function destroy(PreAuthorization $preAuthorization, Request $request): JsonResponse
    {
        if ($preAuthorization->host_id !== $request->user()->id) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $preAuthorization->update(['status' => 'cancelled']);

        return response()->json(['message' => 'Pre-autorización cancelada.']);
    }
}
