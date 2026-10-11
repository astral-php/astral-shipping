<?php

declare(strict_types=1);

namespace AstralShipping\Tests;

use AstralShipping\Exception\ShippingException;
use AstralShipping\Method\SendcloudMethod;
use AstralShipping\Tests\Support\FakeHttpClient;
use AstralShipping\Tests\Support\Factory;
use PHPUnit\Framework\TestCase;

final class SendcloudMethodTest extends TestCase
{
    private const BODY = '{"shipping_methods":['
        . '{"id":117,"name":"Home 20kg","carrier":"dhl","min_weight":"0.001","max_weight":"20.000","service_point_input":"none",'
        . '"countries":[{"iso_2":"FR","price":7.9,"lead_time_hours":48},{"iso_2":"BE","price":5.2,"lead_time_hours":24}]},'
        . '{"id":200,"name":"Relay 1kg","carrier":"mondial_relay","min_weight":"0.001","max_weight":"1.000","service_point_input":"required",'
        . '"countries":[{"iso_2":"FR","price":4.5,"lead_time_hours":72}]}'
        . ']}';

    public function testConvertsMethodsToQuotes(): void
    {
        $http   = new FakeHttpClient(200, self::BODY);
        $method = new SendcloudMethod($http, 'pub', 'sec');

        $quotes = $method->quotes(Factory::context(weight: 500));

        $this->assertCount(2, $quotes);
        $this->assertSame('sendcloud:117', $quotes[0]->code);
        $this->assertSame(790, $quotes[0]->priceCents);
        $this->assertSame(2, $quotes[0]->estimatedDays);
        $this->assertFalse($quotes[0]->requiresPickupPoint);
        $this->assertSame(450, $quotes[1]->priceCents);
        $this->assertTrue($quotes[1]->requiresPickupPoint);
        $this->assertSame('Basic ' . base64_encode('pub:sec'), $http->lastHeaders['Authorization']);
    }

    public function testFiltersByWeightAndCountry(): void
    {
        $method = new SendcloudMethod(new FakeHttpClient(200, self::BODY), 'pub', 'sec');

        $heavy = $method->quotes(Factory::context(weight: 1500));
        $this->assertCount(1, $heavy);
        $this->assertSame('sendcloud:117', $heavy[0]->code);

        $this->assertSame([], $method->quotes(Factory::context(weight: 500, country: 'DE')));
    }

    public function testMarkupAndCarrierFilter(): void
    {
        $method = new SendcloudMethod(
            new FakeHttpClient(200, self::BODY), 'pub', 'sec',
            markupCents: 100, allowedCarriers: ['mondial_relay'],
        );

        $quotes = $method->quotes(Factory::context(weight: 500));

        $this->assertCount(1, $quotes);
        $this->assertSame(550, $quotes[0]->priceCents);
    }

    public function testUsesTheCacheClosure(): void
    {
        $http   = new FakeHttpClient(200, self::BODY);
        $store  = [];
        $remember = function (string $key, int $ttl, callable $producer) use (&$store) {
            return $store[$key] ??= $producer();
        };
        $method = new SendcloudMethod($http, 'pub', 'sec', $remember);

        $method->quotes(Factory::context());
        $method->quotes(Factory::context());

        $this->assertSame(1, $http->calls);
    }

    public function testHttpErrorThrowsShippingException(): void
    {
        $this->expectException(ShippingException::class);

        (new SendcloudMethod(new FakeHttpClient(401, '{}'), 'pub', 'bad'))->quotes(Factory::context());
    }
}
