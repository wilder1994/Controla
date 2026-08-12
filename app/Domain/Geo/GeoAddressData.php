<?php

declare(strict_types=1);

namespace App\Domain\Geo;

final readonly class GeoAddressData
{
    public function __construct(
        public ?string $address = null,
        public ?string $city = null,
        public ?string $department = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
    ) {}

    /** @param array<string, mixed> $validated */
    public static function fromValidated(array $validated): self
    {
        return new self(
            address: isset($validated['address']) && $validated['address'] !== ''
                ? (string) $validated['address']
                : null,
            city: isset($validated['city']) && $validated['city'] !== ''
                ? (string) $validated['city']
                : null,
            department: isset($validated['department']) && $validated['department'] !== ''
                ? (string) $validated['department']
                : null,
            latitude: isset($validated['latitude']) && $validated['latitude'] !== '' && $validated['latitude'] !== null
                ? (float) $validated['latitude']
                : null,
            longitude: isset($validated['longitude']) && $validated['longitude'] !== '' && $validated['longitude'] !== null
                ? (float) $validated['longitude']
                : null,
        );
    }

    /** @return array<string, mixed> */
    public function toModelAttributes(): array
    {
        return [
            'address' => $this->address,
            'city' => $this->city,
            'department' => $this->department,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }

    /** @return list<string> */
    public static function formKeys(): array
    {
        return ['address', 'city', 'department', 'latitude', 'longitude'];
    }
}
