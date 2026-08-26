<?php

declare(strict_types=1);

namespace Tests\Unit\Company;

use App\Enums\SupervisorFieldModule;
use App\Enums\SupervisorFieldOutcome;
use App\Services\Company\AssertSupervisorFieldPayload;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class AssertSupervisorFieldPayloadTest extends TestCase
{
    public function test_inventory_novelty_is_attention(): void
    {
        $validated = app(AssertSupervisorFieldPayload::class)->execute(
            SupervisorFieldModule::Inventory,
            ['condition' => 'novelty'],
        );

        $this->assertSame(SupervisorFieldOutcome::Attention, $validated->outcome);
        $this->assertSame('novelty', $validated->payload['condition']);
    }

    public function test_alarm_fail_is_critical(): void
    {
        $validated = app(AssertSupervisorFieldPayload::class)->execute(
            SupervisorFieldModule::Alarms,
            ['result' => 'fail'],
        );

        $this->assertSame(SupervisorFieldOutcome::Critical, $validated->outcome);
    }

    public function test_rejects_unknown_document_kind(): void
    {
        $this->expectException(ValidationException::class);

        app(AssertSupervisorFieldPayload::class)->execute(
            SupervisorFieldModule::Documents,
            ['kind' => 'contrato_comercial', 'status' => 'delivered', 'quantity' => 1],
        );
    }
}
