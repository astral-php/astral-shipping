<?php

declare(strict_types=1);

namespace AstralShipping\Tests;

use AstralShipping\Contract\ShippingMethodInterface;
use AstralShipping\Exception\InvalidSelectionException;
use AstralShipping\Exception\ShippingException;
use AstralShipping\Method\FlatRateMethod;
use AstralShipping\Method\MondialRelayMethod;
use AstralShipping\Method\StorePickupMethod;
use AstralShipping\Models\ShippingContext;
use AstralShipping\Rate\ArrayRateTable;
use AstralShipping\Service\ShippingCalculator;
use AstralShipping\Service\ShippingService;
use AstralShipping\Tests\Support\Factory;
use PHPUnit\Framework\TestCase;

final class ShippingCalculatorTest extends TestCase
{
    private function calculator(?\Closure $onError = null): ShippingCalculator
    {
        $rates = new ArrayRateTable([
            ['method' => 'mondial_relay', 'country' => 'FR', 'min_g' => 0, 'max_g' => 3000, 'price_cents' => 420],
        ]);

        return (new ShippingCalculator($onError))
            ->register(new FlatRateMethod(590))
            ->register(new MondialRelayMethod($rates))
            ->register(new StorePickupMethod());
    }

    public function testQuotesAreSortedByPrice(): void
    {
        $codes = array_map(fn($q) => $q->code, $this->calculator()->quotes(Factory::context()));

        $this->assertSame(['store_pickup', 'mondial_relay', 'flat_rate'], $codes);
    }

    public function testDuplicateRegistrationIsRejected(): void
    {
        $this->expectException(ShippingException::class);

        $this->calculator()->register(new FlatRateMethod(100));
    }

    public function testFailingMethodIsIsolatedAndReported(): void
    {
        $failing = new class implements ShippingMethodInterface {
            public function code(): string { return 'broken_api'; }
            public function label(): string { return 'Broken'; }
            public function quotes(ShippingContext $context): array { throw new ShippingException('API down'); }
        };

        $reported = [];
        $calc = $this->calculator(function ($method, $e) use (&$reported): void {
            $reported[] = $method->code() . ':' . $e->getMessage();
        });
        $calc->register($failing);

        $this->assertCount(3, $calc->quotes(Factory::context()));
        $this->assertSame(['broken_api:API down'], $reported);
    }

    public function testCheapest(): void
    {
        $this->assertSame('store_pickup', $this->calculator()->cheapest(Factory::context())->code);
    }

    public function testSelectRecomputesPriceAndTotals(): void
    {
        $service   = new ShippingService($this->calculator());
        $selection = $service->select(Factory::context(), 'flat_rate');

        $this->assertSame(590, $selection->quote->priceCents);
        $this->assertNull($selection->pickupPoint);
        $this->assertSame(5590, $service->total(5000, $selection));
    }

    public function testPickupPointIsMandatoryWhenRequired(): void
    {
        $service = new ShippingService($this->calculator());

        $this->expectException(InvalidSelectionException::class);
        $service->select(Factory::context(), 'mondial_relay', '');
    }

    public function testPickupPointIsKept(): void
    {
        $service   = new ShippingService($this->calculator());
        $selection = $service->select(Factory::context(), 'mondial_relay', '012345', 'Relais Tabac');

        $this->assertSame('012345', $selection->pickupPoint?->id);
        $this->assertSame('mondial_relay', $selection->pickupPoint?->carrier);
    }

    public function testUnknownMethodIsRejected(): void
    {
        $this->expectException(InvalidSelectionException::class);

        (new ShippingService($this->calculator()))->select(Factory::context(), 'does_not_exist');
    }

    public function testEventIsDispatched(): void
    {
        $events  = [];
        $service = new ShippingService($this->calculator(), function ($event) use (&$events): void {
            $events[] = $event;
        });

        $service->select(Factory::context(), 'flat_rate');

        $this->assertCount(1, $events);
    }
}
