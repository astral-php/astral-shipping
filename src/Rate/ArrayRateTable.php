<?php

declare(strict_types=1);

namespace AstralShipping\Rate;

use AstralShipping\Contract\RateLookupInterface;
use AstralShipping\Models\Rate;

/**
 * Barème en mémoire (config PHP) : idéal pour démarrer sans base, ou pour les tests.
 *
 * Chaque ligne : ['method' => 'colissimo_home', 'country' => 'FR', 'min_g' => 0, 'max_g' => 500,
 *                 'price_cents' => 495, 'days' => 2]   (country '*' = tous pays, days optionnel)
 */
final class ArrayRateTable implements RateLookupInterface
{
    /** @param list<array<string, mixed>> $rows */
    public function __construct(private array $rows = [])
    {
    }

    public function findRate(string $methodCode, string $country, int $weightGrams): ?Rate
    {
        $country = strtoupper($country);
        $best    = null;
        $bestKey = null;

        foreach ($this->rows as $row) {
            if (($row['method'] ?? null) !== $methodCode) {
                continue;
            }
            $rowCountry = strtoupper((string) ($row['country'] ?? '*'));
            if ($rowCountry !== $country && $rowCountry !== '*') {
                continue;
            }
            if ($weightGrams < (int) ($row['min_g'] ?? 0) || $weightGrams > (int) ($row['max_g'] ?? PHP_INT_MAX)) {
                continue;
            }

            // Pays exact (0) avant joker (1), puis prix croissant.
            $key = [$rowCountry === '*' ? 1 : 0, (int) $row['price_cents']];
            if ($bestKey === null || $key < $bestKey) {
                $bestKey = $key;
                $best    = $row;
            }
        }

        if ($best === null) {
            return null;
        }

        return new Rate((int) $best['price_cents'], isset($best['days']) ? (int) $best['days'] : null);
    }
}
