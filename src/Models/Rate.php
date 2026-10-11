<?php

declare(strict_types=1);

namespace AstralShipping\Models;

/** Tarif trouvé dans un barème. */
final class Rate
{
    public function __construct(
        public readonly int $priceCents,
        public readonly ?int $estimatedDays = null,
    ) {
    }
}
