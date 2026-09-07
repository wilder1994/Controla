<?php

declare(strict_types=1);

namespace Tests\Unit\Employee;

use App\Enums\BloodGroup;
use PHPUnit\Framework\TestCase;

final class BloodGroupTest extends TestCase
{
    public function test_parses_pending_blood_group(): void
    {
        $this->assertSame(BloodGroup::Pending, BloodGroup::tryParse('Pendiente'));
        $this->assertSame(BloodGroup::Pending, BloodGroup::tryParse('PENDIENTE'));
        $this->assertSame(BloodGroup::OPositive, BloodGroup::tryParse('O+ (Más común)'));
        $this->assertNull(BloodGroup::tryParse('XYZ'));
    }
}
