<?php

declare(strict_types=1);

namespace App\Domain\Pricing\Data;

use App\Enums\CompanyPackageSku;
use App\Enums\PackageModality;
use InvalidArgumentException;

final readonly class AccessSeatSplit
{
    public function __construct(
        public int $manual,
        public int $hardware,
    ) {
        if ($this->manual < 0 || $this->hardware < 0) {
            throw new InvalidArgumentException('Los asientos no pueden ser negativos.');
        }

        if ($this->size() < 1) {
            throw new InvalidArgumentException('El cupo de Accesos debe ser al menos 1.');
        }
    }

    public function size(): int
    {
        return $this->manual + $this->hardware;
    }

    public function modality(): PackageModality
    {
        if ($this->hardware === 0) {
            return PackageModality::Manual;
        }

        if ($this->manual === 0) {
            return PackageModality::Hardware;
        }

        return PackageModality::Mixed;
    }

    public function sku(): CompanyPackageSku
    {
        return CompanyPackageSku::fromParts($this->size(), $this->modality());
    }

    public function label(): string
    {
        $size = $this->size();
        $clients = $size === 1 ? '1 cliente' : "{$size} clientes";

        if ($this->modality() !== PackageModality::Mixed) {
            return "{$clients} · {$this->modality()->label()}";
        }

        return "{$clients} · {$this->manual} sin hardware + {$this->hardware} con hardware";
    }

    public static function fromSku(CompanyPackageSku $sku): self
    {
        return $sku->modality() === PackageModality::Hardware
            ? new self(0, $sku->size())
            : new self($sku->size(), 0);
    }

    public static function resolve(CompanyPackageSku $sku, ?int $manual, ?int $hardware): self
    {
        if ($manual === null && $hardware === null) {
            return self::fromSku($sku);
        }

        $manual ??= 0;
        $hardware ??= 0;
        $split = new self($manual, $hardware);

        if ($split->size() !== $sku->size()) {
            throw new InvalidArgumentException(
                'La suma de asientos debe coincidir con el cupo del paquete ('.$sku->size().').',
            );
        }

        if ($sku->size() === 1 && $split->modality() === PackageModality::Mixed) {
            throw new InvalidArgumentException('El paquete de 1 cliente no admite mixto.');
        }

        return $split;
    }
}
