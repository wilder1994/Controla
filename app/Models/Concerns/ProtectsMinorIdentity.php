<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\User;
use App\Support\Privacy\MinorPersonalData;

trait ProtectsMinorIdentity
{
    public function isMinor(): bool
    {
        return MinorPersonalData::isMinor($this->birth_date ?? null);
    }

    public function revealsPii(?User $actor = null): bool
    {
        return MinorPersonalData::shouldRevealPii($actor ?? auth()->user(), $this->birth_date ?? null);
    }

    public function displayedDocument(?User $actor = null): string
    {
        return MinorPersonalData::documentForDisplay(
            $actor ?? auth()->user(),
            $this->birth_date ?? null,
            $this->document_type ?? null,
            $this->document_number ?? null,
        );
    }

    public function displayedContact(?string $value, ?User $actor = null): string
    {
        return MinorPersonalData::contactForDisplay(
            $actor ?? auth()->user(),
            $this->birth_date ?? null,
            $value,
        );
    }

    /** @return array<string, mixed> */
    public function porteriaIdentity(): array
    {
        return MinorPersonalData::redactIdentityArray(
            $this->only(['id', 'document_type', 'document_number', 'first_name', 'last_name', 'company', 'phone', 'email']),
            $this->birth_date ?? null,
        );
    }
}
