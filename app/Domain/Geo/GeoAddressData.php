<?php

declare(strict_types=1);

namespace App\Domain\Geo;

final readonly class GeoAddressData
{
    public function __construct(
        public ?string $address = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
    ) {}

    /** @param array<string, mixed> $validated */
    public static function fromValidated(array $validated): self
    {
        return new self(
            address: isset($validated['address']) ? (string) $validated['address'] : null,
            latitude: isset($validated['latitude']) ? (float) $validated['latitude'] : null,
            longitude: isset($validated['longitude']) ? (float) $validated['longitude'] : null,
        );
    }

    /** @return array<string, mixed> */
    public function toModelAttributes(): array
    {
        return [
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }
}
