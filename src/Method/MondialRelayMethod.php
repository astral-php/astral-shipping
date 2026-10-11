<?php

declare(strict_types=1);

namespace AstralShipping\Method;

use AstralShipping\Contract\RateLookupInterface;

/**
 * Mondial Relay : grille tarifaire (codes `mondial_relay` pour le point relais, `mondial_relay_home` à domicile).
 * Le point relais est choisi côté navigateur (widget officiel) et transmis via `pickup_point_id`.
 */
final class MondialRelayMethod extends TableRateMethod
{
    public function __construct(RateLookupInterface $rates, bool $relay = true, ?int $freeThresholdCents = null)
    {
        parent::__construct(
            code: $relay ? 'mondial_relay' : 'mondial_relay_home',
            label: $relay ? 'Mondial Relay — point relais' : 'Mondial Relay — à domicile',
            carrier: 'mondial_relay',
            rates: $rates,
            requiresPickupPoint: $relay,
            freeThresholdCents: $freeThresholdCents,
        );
    }
}
