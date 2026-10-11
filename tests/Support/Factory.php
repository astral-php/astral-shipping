<?php

declare(strict_types=1);

namespace AstralShipping\Tests\Support;

use AstralShipping\Models\Address;
use AstralShipping\Models\ShippingContext;

final class Factory
{
    public static function context(int $subtotal = 5000, int $weight = 800, string $country = 'FR'): ShippingContext
    {
        return new ShippingContext($subtotal, $weight, new Address($country, '75001', 'Paris'));
    }
}
