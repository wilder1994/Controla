<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\IdentityDocumentType;
use Illuminate\Database\Seeder;

final class IdentityDocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['code' => 'CC', 'name' => 'Cédula de ciudadanía', 'sort_order' => 10],
            ['code' => 'CE', 'name' => 'Cédula de extranjería', 'sort_order' => 20],
            ['code' => 'NIT', 'name' => 'NIT', 'sort_order' => 30],
            ['code' => 'PA', 'name' => 'Pasaporte', 'sort_order' => 40],
        ];

        foreach ($types as $type) {
            IdentityDocumentType::query()->updateOrCreate(
                ['code' => $type['code']],
                [
                    'name' => $type['name'],
                    'sort_order' => $type['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }
}
