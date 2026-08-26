<?php

declare(strict_types=1);

namespace App\Enums;

enum SupervisionPackageSku: string
{
    case Sit1 = 'sup_1';
    case Sit5 = 'sup_5';
    case Sit10 = 'sup_10';
    case Sit20 = 'sup_20';
    case Sit50 = 'sup_50';
    case Sit100 = 'sup_100';
    case Unlimited = 'sup_unlimited';

    public function isUnlimited(): bool
    {
        return $this === self::Unlimited;
    }

    public function size(): int
    {
        return $this->isUnlimited() ? 100 : (int) explode('_', $this->value)[1];
    }

    public function seatCap(): ?int
    {
        return $this->isUnlimited() ? null : $this->size();
    }

    public function label(): string
    {
        if ($this->isUnlimited()) {
            return 'Ilimitada · Supervisión';
        }

        $size = $this->size();
        $sites = $size === 1 ? '1 cliente' : "{$size} clientes";

        return "{$sites} · Supervisión";
    }

    public static function fromSize(int $size): self
    {
        return self::from('sup_'.$size);
    }

    /** Catálogo vendible suelto (sin el 20, que solo existe como oferta de Accesos 10). */
    /** @return list<self> */
    public static function standaloneCases(): array
    {
        return [
            self::Sit1,
            self::Sit5,
            self::Sit10,
            self::Sit50,
            self::Sit100,
            self::Unlimited,
        ];
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }

    /** @return array<string, string> */
    public static function standaloneOptions(): array
    {
        return collect(self::standaloneCases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }

    /** @return array<string, string> */
    public static function selectableOptions(?int $accessSize = null): array
    {
        $options = self::standaloneOptions();
        $offer = $accessSize !== null ? self::offerForAccessSize($accessSize) : null;
        if ($offer !== null && ! isset($options[$offer->value])) {
            $options[$offer->value] = $offer->label().' (oferta Accesos)';
        }

        return $options;
    }

    public static function offerForAccessSize(int $accessSize): ?self
    {
        return match (true) {
            $accessSize < 5 => null,
            $accessSize === 5 => self::Sit10,
            $accessSize === 10 => self::Sit20,
            $accessSize >= 50 => self::Unlimited,
            default => null,
        };
    }
}
