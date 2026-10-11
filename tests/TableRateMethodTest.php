<?php

declare(strict_types=1);

namespace AstralShipping\Tests;

use AstralShipping\Method\ColissimoMethod;
use AstralShipping\Method\MondialRelayMethod;
use AstralShipping\Rate\ArrayRateTable;
use AstralShipping\Tests\Support\Factory;
use PHPUnit\Framework\TestCase;

final class TableRateMethodTest extends TestCase
{
    private function table(): ArrayRateTable
    {
        return new ArrayRateTable([
            ['method' => 'colissimo_home', 'country' => 'FR', 'min_g' => 0,   'max_g' => 500,  'price_cents' => 495, 'days' => 2],
            ['method' => 'colissimo_home', 'country' => 'FR', 'min_g' => 501, 'max_g' => 1000, 'price_cents' => 650, 'days' => 2],
            ['method' => 'colissimo_home', 'country' => '*',  'min_g' => 0,   'max_g' => 1000, 'price_cents' => 1500],
            ['method' => 'mondial_relay',  'country' => 'FR', 'min_g' => 0,   'max_g' => 3000, 'price_cents' => 420, 'days' => 3],
        ]);
    }

    public function testPicksTheWeightBracket(): void
    {
        $method = new ColissimoMethod($this->table());

        $this->assertSame(495, $method->quotes(Factory::context(weight: 500))[0]->priceCents);
        $this->assertSame(650, $method->quotes(Factory::context(weight: 501))[0]->priceCents);
    }

    public function testExactCountryWinsOverWildcard(): void
    {
        $method = new ColissimoMethod($this->table());

        $this->assertSame(650, $method->quotes(Factory::context(weight: 800, country: 'FR'))[0]->priceCents);
        $this->assertSame(1500, $method->quotes(Factory::context(weight: 800, country: 'DE'))[0]->priceCents);
    }

    public function testNoRateMeansNoOffer(): void
    {
        $method = new ColissimoMethod($this->table());

        $this->assertSame([], $method->quotes(Factory::context(weight: 5000)));
    }

    public function testMondialRelayRequiresPickupPoint(): void
    {
        $quote = (new MondialRelayMethod($this->table()))->quotes(Factory::context())[0];

        $this->assertSame('mondial_relay', $quote->code);
        $this->assertTrue($quote->requiresPickupPoint);
        $this->assertSame(3, $quote->estimatedDays);
    }

    public function testFreeThresholdOverridesRate(): void
    {
        $method = new ColissimoMethod($this->table(), false, 5000);

        $this->assertSame(0, $method->quotes(Factory::context(subtotal: 6000))[0]->priceCents);
    }
}
