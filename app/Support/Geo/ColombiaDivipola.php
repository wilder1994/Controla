<?php

declare(strict_types=1);

namespace App\Support\Geo;

final class ColombiaDivipola
{
    /** @var array<string, list<string>>|null */
    private static ?array $tree = null;

    /** @return array<string, list<string>> */
    public static function tree(): array
    {
        if (self::$tree !== null) {
            return self::$tree;
        }

        $path = resource_path('data/colombia-divipola.json');
        $raw = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $tree = [];

        foreach ($raw as $row) {
            $department = trim((string) ($row['departamento'] ?? ''));
            if ($department === '') {
                continue;
            }

            $cities = array_values(array_unique(array_map(
                static fn (mixed $city): string => trim((string) $city),
                $row['ciudades'] ?? [],
            )));
            $cities = array_values(array_filter($cities, static fn (string $city): bool => $city !== ''));

            if ($department === 'Cundinamarca') {
                $cities = array_values(array_filter($cities, static fn (string $city): bool => $city !== 'Bogotá'));
            }

            sort($cities, SORT_NATURAL | SORT_FLAG_CASE);
            $tree[$department] = $cities;
        }

        $tree['Bogotá D.C.'] = ['Bogotá D.C.'];
        ksort($tree, SORT_NATURAL | SORT_FLAG_CASE);

        return self::$tree = $tree;
    }

    /** @return list<string> */
    public static function departments(): array
    {
        return array_keys(self::tree());
    }

    /** @return list<string> */
    public static function municipalities(string $department): array
    {
        return self::tree()[$department] ?? [];
    }

    public static function hasDepartment(string $department): bool
    {
        return array_key_exists($department, self::tree());
    }

    public static function hasMunicipality(string $department, string $city): bool
    {
        return in_array($city, self::municipalities($department), true);
    }

    /** @return array<string, mixed> */
    public static function placeRules(string $departmentField, string $cityField): array
    {
        return [
            $departmentField => [
                'nullable',
                'string',
                'max:120',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $department = trim((string) $value);
                    if ($department !== '' && ! self::hasDepartment($department)) {
                        $fail('Selecciona un departamento de la lista.');
                    }
                },
            ],
            $cityField => [
                'nullable',
                'string',
                'max:120',
                function (string $attribute, mixed $value, \Closure $fail) use ($departmentField): void {
                    $city = trim((string) $value);
                    $department = trim((string) request()->input($departmentField, ''));
                    if ($city === '') {
                        return;
                    }
                    if ($department === '' || ! self::hasMunicipality($department, $city)) {
                        $fail('Selecciona un municipio de ese departamento.');
                    }
                },
            ],
        ];
    }
}
