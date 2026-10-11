<?php

declare(strict_types=1);

namespace AstralShipping\Models;

/** Choix final validé : une offre + (si requis) un point relais. */
final class ShippingSelection
{
    public function __construct(
        public readonly ShippingQuote $quote,
        public readonly ?PickupPoint $pickupPoint = null,
    ) {
    }
}
