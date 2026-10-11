<?php

declare(strict_types=1);

namespace AstralShipping\Method;

use AstralShipping\Contract\RateLookupInterface;

/**
 * Colissimo : grille tarifaire (codes `colissimo_home` ou `colissimo_pickup`).
 * Les tarifs sont ceux de TON contrat : à saisir en base ou à importer en CSV.
 */
final class ColissimoMethod extends TableRateMethod
{
    public function __construct(RateLookupInterface $rates, bool $pickup = false, ?int $freeThresholdCents = null)
    {
        parent::__construct(
            code: $pickup ? 'colissimo_pickup' : 'colissimo_home',
            label: $pickup ? 'Colissimo — point de retrait' : 'Colissimo — à domicile',
            carrier: 'colissimo',
            rates: $rates,
            requiresPickupPoint: $pickup,
            freeThresholdCents: $freeThresholdCents,
        );
    }
}
