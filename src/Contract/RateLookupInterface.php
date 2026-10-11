<?php

declare(strict_types=1);

namespace AstralShipping\Contract;

use AstralShipping\Models\Rate;

/** Source de barèmes : base de données (ShippingRateDao) ou tableau de config (ArrayRateTable). */
interface RateLookupInterface
{
    /**
     * Pays exact prioritaire sur le joker "*" ; à égalité, le tarif le plus bas.
     * Les bornes de poids sont inclusives (en grammes).
     */
    public function findRate(string $methodCode, string $country, int $weightGrams): ?Rate;
}
