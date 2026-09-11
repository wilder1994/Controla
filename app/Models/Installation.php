<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ColombianAreaKind;
use App\Models\Concerns\BelongsToClient;
use App\Support\Geo\ColombianArea;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

final class Installation extends Model
{
    use BelongsToClient, SoftDeletes;

    protected $fillable = [
        'client_id',
        'name',
        'code',
        'kind',
        'dane_code',
        'commune',
        'area_kind',
        'rector_user_id',
        'is_client_site',
        'is_active',
        'address',
        'city',
        'department',
        'latitude',
        'longitude',
    ];

    protected function casts(): array
    {
        return [
            'is_client_site' => 'boolean',
            'is_active' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Installation $installation): void {
            if (filled($installation->code) || ! $installation->client_id) {
                return;
            }

            $client = $installation->relationLoaded('client')
                ? $installation->client
                : Client::query()->find($installation->client_id);

            if ($client instanceof Client) {
                $installation->code = self::nextAvailableCode($client);
            }
        });
    }

    public static function nextAvailableCode(Client $client, ?int $ignoreId = null): string
    {
        $raw = $client->slug ?: 'sed';
        $prefix = Str::upper(Str::substr((string) preg_replace('/[^A-Za-z0-9]+/', '', $raw), 0, 8)) ?: 'SED';
        $n = 1;

        do {
            $candidate = $prefix.'-'.str_pad((string) $n, 2, '0', STR_PAD_LEFT);
            $taken = self::query()
                ->withoutGlobalScopes()
                ->where('client_id', $client->id)
                ->where('code', $candidate)
                ->when($ignoreId !== null, fn ($q) => $q->whereKeyNot($ignoreId))
                ->exists();
            $n++;
        } while ($taken);

        return $candidate;
    }

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    public function rector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rector_user_id');
    }

    public function kindLabel(): string
    {
        return \App\Enums\InstallationKind::tryFrom((string) $this->kind)?->label() ?? '—';
    }

    public function requiresOfficialCode(): bool
    {
        return \App\Enums\InstallationKind::tryFrom((string) $this->kind)?->requiresOfficialCode() ?? false;
    }

    /** @return \Illuminate\Support\Collection<int, string> */
    public function staffLines(): \Illuminate\Support\Collection
    {
        $people = $this->relationLoaded('assignedAdmins')
            ? $this->assignedAdmins
            : $this->assignedAdmins()->get();

        if ($people->isEmpty()) {
            $fallback = \App\Support\Company\InstallationSiteAdmins::label($this->rector);

            return $fallback === '—' ? collect() : collect([$fallback]);
        }

        return $people
            ->map(static function (User $user): string {
                $permission = ($user->pivot->site_permission ?? 'admin') === 'support' ? 'Apoyo' : 'Admin';

                return \App\Support\Company\InstallationSiteAdmins::label($user).' · '.$permission;
            })
            ->filter()
            ->values();
    }

    public function siteAdminLabel(): string
    {
        $lines = $this->staffLines();

        if ($lines->isEmpty()) {
            return '—';
        }

        if ($lines->count() <= 2) {
            return $lines->implode(', ');
        }

        return $lines->first().' +'.($lines->count() - 1);
    }

    public function areaKind(): ColombianAreaKind
    {
        return ColombianAreaKind::tryFrom((string) $this->area_kind)
            ?? ColombianArea::classify($this->commune, $this->city);
    }

    public function areaKindLabel(): string
    {
        return $this->areaKind()->label();
    }

    public function assignedAdmins(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'client_user_installation_assignments')
            ->withPivot('site_permission')
            ->withTimestamps()
            ->orderBy('users.name');
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    public function supervisorPosts(): HasMany
    {
        return $this->hasMany(SupervisorPost::class);
    }

    public function structures(): HasMany
    {
        return $this->hasMany(Structure::class);
    }

    public function hasDoors(): bool
    {
        return Location::query()
            ->withoutGlobalScopes()
            ->where('installation_id', $this->id)
            ->where('is_active', true)
            ->exists();
    }
}
