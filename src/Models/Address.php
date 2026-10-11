<?php

declare(strict_types=1);

namespace AstralShipping\Models;

/** Adresse de livraison (le pays est normalisé en ISO 3166-1 alpha-2 majuscule). */
final class Address
{
    public readonly string $country;

    public function __construct(
        string $country,
        public readonly string $postalCode = '',
        public readonly string $city = '',
        public readonly string $line1 = '',
    ) {
        $this->country = strtoupper(trim($country));
    }
}
