<?php

declare(strict_types=1);

namespace App\Services\Company;

use App\Models\Employee;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

final class StoreEmployeePhotoService
{
    public function store(Employee $employee, UploadedFile $photo): Employee
    {
        $this->delete($employee);

        $ext = $photo->guessExtension() ?: 'jpg';
        $path = $photo->storeAs(
            'employees/'.$employee->security_company_id,
            $employee->id.'.'.$ext,
            'local',
        );

        $employee->update(['photo_path' => $path ?: null]);

        return $employee->fresh();
    }

    public function delete(Employee $employee): void
    {
        if ($employee->photo_path && Storage::disk('local')->exists($employee->photo_path)) {
            Storage::disk('local')->delete($employee->photo_path);
        }
    }
};
