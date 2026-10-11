<?php

declare(strict_types=1);

namespace AstralShipping\Method;

use AstralShipping\Contract\ShippingMethodInterface;
use AstralShipping\Models\ShippingContext;
use AstralShipping\Models\ShippingQuote;

/** Forfait unique, éventuellement limité à certains pays et gratuit au-delà d'un seuil. */
final class FlatRateMethod implements ShippingMethodInterface
{
    /**
     * @param list<string> $countries Pays autorisés (ISO2) ; vide = tous
     */
    public function __construct(
        private int $priceCents,
        private string $label = 'Livraison standard',
        private string $code = 'flat_rate',
        private array $countries = [],
        private ?int $freeThresholdCents = null,
        private ?int $estimatedDays = null,
        private string $carrier = 'shop',
    ) {
        $this->countries = array_map('strtoupper', $countries);
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
        if ($this->countries !== [] && !in_array($context->destination->country, $this->countries, true)) {
            return [];
        }

        $free  = $this->freeThresholdCents !== null && $context->subtotalCents >= $this->freeThresholdCents;
        $price = $free ? 0 : $this->priceCents;

        return [new ShippingQuote(
            code: $this->code,
            label: $this->label,
            carrier: $this->carrier,
            priceCents: $price,
            currency: $context->currency,
            estimatedDays: $this->estimatedDays,
        )];
    }
}
