<?php

declare(strict_types=1);

namespace AstralShipping\Method;

use AstralShipping\Contract\RateLookupInterface;
use AstralShipping\Contract\ShippingMethodInterface;
use AstralShipping\Models\ShippingContext;
use AstralShipping\Models\ShippingQuote;

/**
 * Barème pays × tranches de poids lu dans un RateLookupInterface.
 * Base de toutes les offres « grille tarifaire » (Colissimo, Mondial Relay, transporteur maison…).
 */
class TableRateMethod implements ShippingMethodInterface
{
    public function __construct(
        protected string $code,
        protected string $label,
        protected string $carrier,
        protected RateLookupInterface $rates,
        protected bool $requiresPickupPoint = false,
        protected ?int $freeThresholdCents = null,
    ) {
    }

    public function code(): string
    {
        return $this->code;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function quotes(ShippingContext $context): array
    {
        $rate = $this->rates->findRate($this->code, $context->destination->country, $context->weightGrams);
        if ($rate === null) {
            return [];
        }

        $free  = $this->freeThresholdCents !== null && $context->subtotalCents >= $this->freeThresholdCents;
        $price = $free ? 0 : $rate->priceCents;

        return [new ShippingQuote(
            code: $this->code,
            label: $this->label,
            carrier: $this->carrier,
            priceCents: $price,
            currency: $context->currency,
            estimatedDays: $rate->estimatedDays,
            requiresPickupPoint: $this->requiresPickupPoint,
        )];
    }
}
