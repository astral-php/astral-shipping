<?php

declare(strict_types=1);

namespace AstralShipping\Tests;

use AstralShipping\Models\Address;
use AstralShipping\Models\ShippingContext;
use PHPUnit\Framework\TestCase;

final class ContextTest extends TestCase
{
    public function testFromLinesSumsQuantities(): void
    {
        $ctx = ShippingContext::fromLines([
            ['weight_g' => 200, 'price_cents' => 1500, 'quantity' => 2],
            ['weight_g' => 500, 'price_cents' => 4000],
        ], new Address('fr'));

        $this->assertSame(7000, $ctx->subtotalCents);
        $this->assertSame(900, $ctx->weightGrams);
        $this->assertSame('FR', $ctx->destination->country);
    }

    public function testNegativeValuesAreRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ShippingContext(-1, 0, new Address('FR'));
    }
}
