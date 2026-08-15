<?php
namespace App\Services\Access;

use App\Models\GuardShift;
use App\Models\User;
use Illuminate\Support\Carbon;

class TurnoService
{
    public function currentFor(User $user): ?GuardShift
    {
        return GuardShift::query()
            ->where('user_id', $user->id)
            ->whereNull('ended_at')
            ->latest('started_at')
            ->first();
    }

    public function hasOpenShiftFor(User $user): bool
    {
        return $this->currentFor($user) !== null;
    }

    public function open(User $user, ?int $locationId, ?string $notes = null): GuardShift
    {
        return GuardShift::create([
            'user_id' => $user->id,
            'client_id' => $user->primary_client_id,
            'location_id' => $locationId,
            'started_at' => now(),
            'start_notes' => $notes,
        ]);
    }

    public function close(User $user, ?string $notes = null): GuardShift
    {
        $shift = $this->currentFor($user);

        if ($shift === null) {
            return $shift;
        }

        $shift->update([
            'ended_at' => now(),
            'end_notes' => $notes,
        ]);

        return $shift;
    }

    public function dailyMinutes(User $user, ?Carbon $date = null): int
    {
        $date ??= today();

        return (int) GuardShift::query()
            ->where('user_id', $user->id)
            ->whereDate('started_at', $date)
            ->whereNotNull('ended_at')
            ->get()
            ->sum(fn (GuardShift $shift) => (int) $shift->started_at->diffInMinutes($shift->ended_at));
    }

    public function isShiftOptionalFor(User $user): bool
    {
        return ! $user->hasRole('guardia');
    }
}