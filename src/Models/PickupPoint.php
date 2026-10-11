<?php

declare(strict_types=1);

namespace AstralShipping\Models;

/** Point relais / consigne choisi par le client (identifiant fourni par le widget du transporteur). */
final class PickupPoint
{
    public function __construct(
        public readonly string $id,
        public readonly string $label = '',
        public readonly string $carrier = '',
    ) {
    }
}
