<?php

declare(strict_types=1);

namespace App\Support\Supervision;

use App\Enums\SupervisorFieldModule;
use App\Enums\SupervisorPostDocumentKind;
use App\Enums\SupervisorRecommendationPriority;

final class FieldModuleCatalog
{
    /**
     * Contrato único app ↔ API. La PWA renderiza estos campos; no hardcodea reglas de negocio.
     *
     * @return list<array<string, mixed>>
     */
    public function modules(): array
    {
        return [
            [
                'key' => 'reviews',
                'label' => 'Revista',
                'hint' => 'Visita de supervisión: cliente, puesto, vigilante y evidencia. No es la minuta de portería.',
                'capture' => 'reviews',
                'requires_client' => true,
                'hangs_off_review' => false,
                'fields' => [
                    [
                        'name' => 'notes',
                        'label' => 'Notas de la visita',
                        'type' => 'textarea',
                        'required' => false,
                        'max' => 2000,
                    ],
                ],
            ],
            $this->fieldModule(SupervisorFieldModule::Inventory, [
                [
                    'name' => 'condition',
                    'label' => 'Estado del puesto',
                    'type' => 'radio',
                    'required' => true,
                    'options' => [
                        ['value' => 'good', 'label' => 'Buen estado'],
                        ['value' => 'novelty', 'label' => 'Con novedad'],
                        ['value' => 'managed', 'label' => 'Novedad gestionada'],
                    ],
                ],
            ]),
            $this->fieldModule(SupervisorFieldModule::Documents, [
                [
                    'name' => 'kind',
                    'label' => 'Tipo documental del puesto',
                    'type' => 'select',
                    'required' => true,
                    'options' => collect(SupervisorPostDocumentKind::cases())
                        ->map(fn (SupervisorPostDocumentKind $kind) => [
                            'value' => $kind->value,
                            'label' => $kind->label(),
                        ])
                        ->values()
                        ->all(),
                ],
                [
                    'name' => 'status',
                    'label' => 'Estado',
                    'type' => 'radio',
                    'required' => true,
                    'options' => [
                        ['value' => 'delivered', 'label' => 'Entregado / al día'],
                        ['value' => 'pending', 'label' => 'Pendiente'],
                    ],
                ],
                [
                    'name' => 'quantity',
                    'label' => 'Cantidad',
                    'type' => 'number',
                    'required' => true,
                    'min' => 1,
                ],
            ]),
            $this->fieldModule(SupervisorFieldModule::Folders, [
                [
                    'name' => 'status',
                    'label' => 'Carpeta del puesto',
                    'type' => 'radio',
                    'required' => true,
                    'options' => [
                        ['value' => 'complete', 'label' => 'Completa'],
                        ['value' => 'missing', 'label' => 'Con faltantes'],
                    ],
                ],
                [
                    'name' => 'missing_items',
                    'label' => 'Qué falta (si aplica)',
                    'type' => 'text',
                    'required' => false,
                    'max' => 500,
                ],
            ]),
            $this->fieldModule(SupervisorFieldModule::Weapons, [
                [
                    'name' => 'serial',
                    'label' => 'Serial observado',
                    'type' => 'text',
                    'required' => true,
                    'max' => 80,
                ],
                [
                    'name' => 'ammo_ok',
                    'label' => 'Munición en regla',
                    'type' => 'checkbox',
                    'required' => false,
                ],
                [
                    'name' => 'novelty',
                    'label' => 'Novedad en el arma',
                    'type' => 'checkbox',
                    'required' => false,
                ],
            ]),
            $this->fieldModule(SupervisorFieldModule::Recommendations, [
                [
                    'name' => 'title',
                    'label' => 'Título',
                    'type' => 'text',
                    'required' => true,
                    'max' => 120,
                ],
                [
                    'name' => 'body',
                    'label' => 'Descripción',
                    'type' => 'textarea',
                    'required' => true,
                    'max' => 2000,
                ],
                [
                    'name' => 'priority',
                    'label' => 'Prioridad',
                    'type' => 'select',
                    'required' => true,
                    'options' => collect(SupervisorRecommendationPriority::cases())
                        ->map(fn (SupervisorRecommendationPriority $priority) => [
                            'value' => $priority->value,
                            'label' => $priority->label(),
                        ])
                        ->values()
                        ->all(),
                ],
                [
                    'name' => 'due_date',
                    'label' => 'Fecha límite',
                    'type' => 'date',
                    'required' => false,
                ],
            ]),
            $this->fieldModule(SupervisorFieldModule::Alarms, [
                [
                    'name' => 'result',
                    'label' => 'Resultado de la prueba',
                    'type' => 'radio',
                    'required' => true,
                    'options' => [
                        ['value' => 'ok', 'label' => 'OK'],
                        ['value' => 'fail', 'label' => 'Falla'],
                    ],
                ],
            ]),
            $this->fieldModule(SupervisorFieldModule::Supports, [
                [
                    'name' => 'reason',
                    'label' => 'Motivo del apoyo',
                    'type' => 'textarea',
                    'required' => true,
                    'max' => 500,
                ],
            ]),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $fields
     * @return array<string, mixed>
     */
    private function fieldModule(SupervisorFieldModule $module, array $fields): array
    {
        return [
            'key' => $module->value,
            'label' => $module->label(),
            'hint' => $module->hint(),
            'capture' => 'logs',
            'requires_client' => $module->requiresClient(),
            'hangs_off_review' => $module->hangsOffReview(),
            'fields' => $fields,
        ];
    }
}
