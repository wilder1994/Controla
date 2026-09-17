<?php

declare(strict_types=1);

namespace App\Services\Access;

use App\Models\Location;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;

final class PorteriaDoorService
{
    public const SESSION_KEY = 'access.operating_location_id';

    public function __construct(
        private readonly TurnoService $turnoService,
    ) {}

    /** @return Collection<int, Location> */
    public function activeDoors(): Collection
    {
        return Location::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function remember(Request $request, int $locationId): void
    {
        $request->session()->put(self::SESSION_KEY, $locationId);
    }

    public function current(Request $request): ?Location
    {
        $user = $request->user();
        if ($user instanceof User) {
            $shift = $this->turnoService->currentFor($user);
            if ($shift?->location_id) {
                $fromShift = Location::query()
                    ->where('is_active', true)
                    ->whereKey($shift->location_id)
                    ->first();
                if ($fromShift !== null) {
                    return $fromShift;
                }
            }
        }

        $id = (int) $request->session()->get(self::SESSION_KEY, 0);
        if ($id < 1) {
            return null;
        }

        return Location::query()
            ->where('is_active', true)
            ->whereKey($id)
            ->first();
    }

    public function bindSingleIfOnlyOne(Request $request): ?Location
    {
        $doors = $this->activeDoors();
        if ($doors->count() === 1) {
            $door = $doors->first();
            $this->remember($request, (int) $door->id);

            return $door;
        }

        return $this->current($request);
    }
}
