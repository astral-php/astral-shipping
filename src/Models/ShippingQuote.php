<?php

declare(strict_types=1);

namespace AstralShipping\Models;

/** Une offre de livraison calculée pour un panier donné. */
final class ShippingQuote
{
    /**
     * @param string               $code Identifiant unique de l'offre (ex. "colissimo_home", "sendcloud:117")
     * @param array<string, mixed> $meta Données libres (id du transporteur, etc.)
     */
    public function __construct(
        public readonly string $code,
        public readonly string $label,
        public readonly string $carrier,
        public readonly int $priceCents,
        public readonly string $currency = 'EUR',
        public readonly ?int $estimatedDays = null,
        public readonly bool $requiresPickupPoint = false,
        public readonly array $meta = [],
    ) {
    }

    public function isFree(): bool
    {
        return $this->priceCents === 0;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'code'                  => $this->code,
            'label'                 => $this->label,
            'carrier'               => $this->carrier,
            'price_cents'           => $this->priceCents,
            'currency'              => $this->currency,
            'estimated_days'        => $this->estimatedDays,
            'requires_pickup_point' => $this->requiresPickupPoint,
            'meta'                  => $this->meta,
        ];
    }
}
