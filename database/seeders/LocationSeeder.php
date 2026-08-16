<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

final class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $points = [
            ['code' => 'PA-01', 'name' => 'Puerta principal', 'address' => 'Av. Principal #1-1'],
            ['code' => 'PA-02', 'name' => 'Puerta de vidrio', 'address' => 'Calle 2 #3-4'],
            ['code' => 'PA-03', 'name' => 'Acceso vehicular', 'address' => 'Entrada parqueaderos'],
            ['code' => 'PA-04', 'name' => 'Portería peatonal', 'address' => 'Calle lateral'],
        ];

        foreach ($points as $point) {
            Location::query()->firstOrCreate(
                ['code' => $point['code'], 'client_id' => null],
                [
                    ...$point,
                    'type' => 'access_point',
                    'is_active' => true,
                ]
            );
        }
    }
}
