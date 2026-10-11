<?php

declare(strict_types=1);

namespace AstralShipping\Events;

use AstralShipping\Models\ShippingSelection;

/**
 * Événement « mode de livraison choisi ».
 * Classe volontairement indépendante du core : voir docs/checkout-integration.md
 * pour l'adapter à `EventInterface` / `EventDispatcher` dans ton application.
 */
final class ShippingMethodSelected
{
    public function __construct(public readonly ShippingSelection $selection)
    {
    }
}
