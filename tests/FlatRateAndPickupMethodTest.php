<?php

declare(strict_types=1);

namespace AstralShipping\Tests;

use AstralShipping\Method\FlatRateMethod;
use AstralShipping\Method\StorePickupMethod;
use AstralShipping\Tests\Support\Factory;
use PHPUnit\Framework\TestCase;

final class FlatRateAndPickupMethodTest extends TestCase
{
    public function testFlatRateReturnsConfiguredPrice(): void
    {
        $quotes = (new FlatRateMethod(590))->quotes(Factory::context());

        $this->assertCount(1, $quotes);
        $this->assertSame(590, $quotes[0]->priceCents);
        $this->assertSame('flat_rate', $quotes[0]->code);
    }

    public function testFlatRateIsFreeAboveThreshold(): void
    {
        $method = new FlatRateMethod(590, freeThresholdCents: 5000);

        $this->assertSame(0, $method->quotes(Factory::context(subtotal: 5000))[0]->priceCents);
        $this->assertSame(590, $method->quotes(Factory::context(subtotal: 4999))[0]->priceCents);
    }

    public function testFlatRateIsRestrictedToCountries(): void
    {
        $method = new FlatRateMethod(590, countries: ['fr', 'be']);

        $this->assertCount(1, $method->quotes(Factory::context(country: 'be')));
        $this->assertSame([], $method->quotes(Factory::context(country: 'DE')));
    }

    public function testStorePickupIsFree(): void
    {
        $quotes = (new StorePickupMethod())->quotes(Factory::context());

        $this->assertSame(0, $quotes[0]->priceCents);
        $this->assertTrue($quotes[0]->isFree());
        $this->assertFalse($quotes[0]->requiresPickupPoint);
    }
}
