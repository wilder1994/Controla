<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Str;
use RuntimeException;

final class AllocateLoginUsername
{
    public function forEmployee(Employee $employee): string
    {
        $first = $this->slugPart($this->firstWord($employee->first_names));
        $last = $this->slugPart($this->firstWord($employee->last_name_paternal ?: ($employee->last_name_maternal ?: '')));
        if ($first === '') {
            $first = 'sup';
        }
        if ($last === '') {
            $last = 'colab';
        }

        $base = $first.'.'.$last;
        for ($i = 0; $i < 50; $i++) {
            $code = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            $username = $base.'.'.$code;
            if (! User::query()->where('username', $username)->exists()) {
                return $username;
            }
        }

        throw new RuntimeException('No se pudo generar un usuario único.');
    }

    public function fromEmail(string $email): string
    {
        $local = strtolower((string) strstr($email, '@', true));
        $base = preg_replace('/[^a-z0-9._-]/', '', $local) ?: 'usuario';

        return $this->unique($base);
    }

    public function randomPassword(): string
    {
        return Str::password(12, true, true, false, false);
    }

    private function unique(string $base): string
    {
        $candidate = $base;
        $n = 1;
        while (User::query()->where('username', $candidate)->exists()) {
            $n++;
            $candidate = $base.'.'.$n;
        }

        return $candidate;
    }

    private function firstWord(string $value): string
    {
        $parts = preg_split('/\s+/u', trim($value)) ?: [];

        return (string) ($parts[0] ?? '');
    }

    private function slugPart(string $value): string
    {
        return (string) Str::of($value)->ascii()->lower()->replaceMatches('/[^a-z]/', '');
    }
}
