<?php

declare(strict_types=1);

namespace Tests\Unit\Supervision;

use App\Support\Supervision\SupervisorPresence;
use Carbon\CarbonImmutable;
use Tests\TestCase;

final class SupervisorPresenceTest extends TestCase
{
    public function test_fresh_gps_with_screen_on_is_online(): void
    {
        $now = CarbonImmutable::parse('2026-09-13 10:00:00');
        $presence = SupervisorPresence::from($now->subSeconds(20), true, $now);

        $this->assertTrue($presence['online']);
        $this->assertSame(SupervisorPresence::ONLINE, $presence['signal']);
        $this->assertSame('En línea', $presence['online_label']);
    }

    public function test_fresh_gps_with_screen_off_is_screen_off(): void
    {
        $now = CarbonImmutable::parse('2026-09-13 10:00:00');
        $presence = SupervisorPresence::from($now->subSeconds(20), false, $now);

        $this->assertTrue($presence['online']);
        $this->assertSame(SupervisorPresence::SCREEN_OFF, $presence['signal']);
        $this->assertSame('Pantalla apagada', $presence['online_label']);
    }

    public function test_stale_gps_is_no_signal_even_if_screen_was_off(): void
    {
        $now = CarbonImmutable::parse('2026-09-13 10:00:00');
        $presence = SupervisorPresence::from($now->subSeconds(91), false, $now);

        $this->assertFalse($presence['online']);
        $this->assertSame(SupervisorPresence::NO_SIGNAL, $presence['signal']);
        $this->assertSame('Sin señal', $presence['online_label']);
    }
}
