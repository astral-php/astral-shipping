<?php

declare(strict_types=1);

namespace AstralShipping\Method;

use AstralShipping\Contract\ShippingMethodInterface;
use AstralShipping\Models\ShippingContext;
use AstralShipping\Models\ShippingQuote;

/** Retrait gratuit en boutique / à l'atelier (aucun point relais à choisir). */
final class StorePickupMethod implements ShippingMethodInterface
{
    public function __construct(
        private string $label = 'Retrait en boutique',
        private string $code = 'store_pickup',
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
        return [new ShippingQuote(
            code: $this->code,
            label: $this->label,
            carrier: 'store',
            priceCents: 0,
            currency: $context->currency,
            meta: ['collect' => true],
        )];
    }
}
