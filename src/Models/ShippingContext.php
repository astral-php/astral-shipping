<?php

declare(strict_types=1);

namespace AstralShipping\Models;

/**
 * Ce qu'il faut savoir du panier pour calculer un port.
 * Montants en centimes (int), poids en grammes (int) : jamais de float.
 */
final class ShippingContext
{
    public function __construct(
        public readonly int $subtotalCents,
        public readonly int $weightGrams,
        public readonly Address $destination,
        public readonly string $currency = 'EUR',
    ) {
        if ($subtotalCents < 0 || $weightGrams < 0) {
            throw new \InvalidArgumentException('Sous-total et poids doivent être positifs ou nuls.');
        }
    }

    /**
     * @param list<array{weight_g:int, price_cents:int, quantity?:int}> $lines
     */
    public static function fromLines(array $lines, Address $destination, string $currency = 'EUR'): self
    {
        $subtotal = 0;
        $weight   = 0;
        foreach ($lines as $line) {
            $qty       = max(1, (int) ($line['quantity'] ?? 1));
            $subtotal += (int) $line['price_cents'] * $qty;
            $weight   += (int) $line['weight_g'] * $qty;
        }

        return new self($subtotal, $weight, $destination, $currency);
    }
}
