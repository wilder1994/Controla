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
    public function test_inventory_bad_item_is_critical(): void
    {
        $validated = app(AssertSupervisorFieldPayload::class)->execute(
            SupervisorFieldModule::Inventory,
            [
                'items' => [
                    ['type' => 'Extintor', 'status' => 'good', 'notes' => null],
                    ['type' => 'Linterna', 'status' => 'bad', 'notes' => 'Sin pila'],
                ],
            ],
        );

        $this->assertSame(SupervisorFieldOutcome::Critical, $validated->outcome);
        $this->assertCount(2, $validated->payload['items']);
    }

    public function test_inventory_regular_item_is_attention(): void
    {
        $validated = app(AssertSupervisorFieldPayload::class)->execute(
            SupervisorFieldModule::Inventory,
            ['items' => [['type' => 'Radio', 'status' => 'regular']]],
        );

        $this->assertSame(SupervisorFieldOutcome::Attention, $validated->outcome);
    }

    public function test_alarm_fail_is_critical(): void
    {
        $validated = app(AssertSupervisorFieldPayload::class)->execute(
            SupervisorFieldModule::Alarms,
            [
                'alarm_type_id' => 1,
                'kind' => 'test',
                'result' => 'fail',
            ],
        );

        $this->assertSame(SupervisorFieldOutcome::Critical, $validated->outcome);
    }

    public function test_alarm_response_real_is_critical(): void
    {
        $validated = app(AssertSupervisorFieldPayload::class)->execute(
            SupervisorFieldModule::Alarms,
            [
                'alarm_type_id' => 1,
                'kind' => 'response',
                'result' => 'real',
            ],
        );

        $this->assertSame(SupervisorFieldOutcome::Critical, $validated->outcome);
    }

    public function test_rejects_invalid_document_type_id(): void
    {
        $this->expectException(ValidationException::class);

        app(AssertSupervisorFieldPayload::class)->execute(
            SupervisorFieldModule::Documents,
            [
                'items' => [
                    ['document_type_id' => 0, 'delivered' => 1, 'pending' => 0],
                ],
            ],
        );
    }

    public function test_documents_pending_is_attention(): void
    {
        $validated = app(AssertSupervisorFieldPayload::class)->execute(
            SupervisorFieldModule::Documents,
            [
                'items' => [
                    ['document_type_id' => 1, 'delivered' => 5, 'pending' => 5, 'notes' => null],
                ],
            ],
        );

        $this->assertSame(SupervisorFieldOutcome::Attention, $validated->outcome);
        $this->assertSame(5, $validated->payload['items'][0]['delivered']);
        $this->assertSame(5, $validated->payload['items'][0]['pending']);
    }

    public function test_documents_all_delivered_is_ok(): void
    {
        $validated = app(AssertSupervisorFieldPayload::class)->execute(
            SupervisorFieldModule::Documents,
            [
                'items' => [
                    ['document_type_id' => 1, 'delivered' => 3, 'pending' => 0],
                ],
            ],
        );

        $this->assertSame(SupervisorFieldOutcome::Ok, $validated->outcome);
    }

    public function test_control_books_novelty_is_attention(): void
    {
        $validated = app(AssertSupervisorFieldPayload::class)->execute(
            SupervisorFieldModule::ControlBooks,
            [
                'items' => [
                    ['control_book_type_id' => 1, 'novelty' => 'yes', 'notes' => 'Hoja suelta'],
                ],
            ],
        );

        $this->assertSame(SupervisorFieldOutcome::Attention, $validated->outcome);
    }

    public function test_control_books_without_novelty_is_ok(): void
    {
        $validated = app(AssertSupervisorFieldPayload::class)->execute(
            SupervisorFieldModule::ControlBooks,
            [
                'items' => [
                    ['control_book_type_id' => 1, 'novelty' => 'no'],
                ],
            ],
        );

        $this->assertSame(SupervisorFieldOutcome::Ok, $validated->outcome);
    }

    public function test_weapons_expired_permit_is_attention(): void
    {
        $validated = app(AssertSupervisorFieldPayload::class)->execute(
            SupervisorFieldModule::Weapons,
            [
                'weapon_type_id' => 1,
                'weapon_brand_id' => 1,
                'serial' => 'AR-9981',
                'caliber' => '9 mm',
                'permit_kind' => 'tenencia',
                'permit_number' => 'PT-1',
                'permit_expires_at' => now()->subDay()->toDateString(),
                'ammo_quantity' => 12,
                'ammo_caliber' => '9 mm',
                'novelty' => 'no',
                'cleaned' => 'no',
            ],
        );

        $this->assertSame(SupervisorFieldOutcome::Attention, $validated->outcome);
    }

    public function test_weapons_valid_permit_is_ok(): void
    {
        $validated = app(AssertSupervisorFieldPayload::class)->execute(
            SupervisorFieldModule::Weapons,
            [
                'weapon_type_id' => 1,
                'weapon_brand_id' => 1,
                'serial' => 'AR-9981',
                'caliber' => '9 mm',
                'permit_kind' => 'deporte',
                'permit_number' => 'PD-1',
                'permit_expires_at' => now()->addYear()->toDateString(),
                'ammo_quantity' => 6,
                'ammo_caliber' => '9 mm',
                'novelty' => 'no',
                'cleaned' => 'yes',
            ],
        );

        $this->assertSame(SupervisorFieldOutcome::Ok, $validated->outcome);
        $this->assertSame('yes', $validated->payload['cleaned']);
    }

    public function test_weapons_novelty_is_attention(): void
    {
        $validated = app(AssertSupervisorFieldPayload::class)->execute(
            SupervisorFieldModule::Weapons,
            [
                'weapon_type_id' => 1,
                'weapon_brand_id' => 1,
                'serial' => 'AR-9981',
                'caliber' => '9 mm',
                'permit_kind' => 'deporte',
                'permit_number' => 'PD-1',
                'permit_expires_at' => now()->addYear()->toDateString(),
                'ammo_quantity' => 6,
                'ammo_caliber' => '9 mm',
                'novelty' => 'yes',
                'notes' => 'Cachas flojas',
                'cleaned' => 'no',
            ],
        );

        $this->assertSame(SupervisorFieldOutcome::Attention, $validated->outcome);
    }

    public function test_recommendations_compute_risk_level_and_outcome(): void
    {
        $validated = app(AssertSupervisorFieldPayload::class)->execute(
            SupervisorFieldModule::Recommendations,
            [
                'items' => [
                    [
                        'risk_type_id' => 1,
                        'risk' => 'Tramos oscuros en el costado norte.',
                        'likelihood' => '5',
                        'impact' => '4',
                        'consequence' => 'Facilita un ingreso no autorizado.',
                        'treatment' => 'Instalar luminarias LED.',
                    ],
                    [
                        'risk_type_id' => 1,
                        'risk' => 'La chapa queda suelta.',
                        'likelihood' => '2',
                        'impact' => '2',
                        'consequence' => 'La puerta no asegura el acceso.',
                        'treatment' => 'Cambiar chapa.',
                    ],
                ],
            ],
        );

        $this->assertSame(SupervisorFieldOutcome::Critical, $validated->outcome);
        $this->assertSame('extreme', $validated->payload['items'][0]['risk_level']);
        $this->assertSame('urgent', $validated->payload['items'][0]['priority']);
        $this->assertSame('low', $validated->payload['items'][1]['risk_level']);
        $this->assertSame(20, $validated->payload['items'][0]['risk_score']);
    }

    public function test_recommendations_reject_more_than_three_items(): void
    {
        $this->expectException(ValidationException::class);

        $item = [
            'risk_type_id' => 1,
            'risk' => 'Descripción del riesgo observado.',
            'likelihood' => '3',
            'impact' => '3',
            'consequence' => 'Afecta la operación del puesto.',
            'treatment' => 'Corregir la condición.',
        ];

        app(AssertSupervisorFieldPayload::class)->execute(
            SupervisorFieldModule::Recommendations,
            ['items' => [$item, $item, $item, $item]],
        );
    }
}
