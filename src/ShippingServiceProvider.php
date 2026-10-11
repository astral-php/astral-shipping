<?php

declare(strict_types=1);

namespace AstralShipping;

use AstralShipping\Contract\HttpClientInterface;
use AstralShipping\Contract\RateLookupInterface;
use AstralShipping\Contract\ShippingMethodInterface;
use AstralShipping\Dao\OrderShipmentDao;
use AstralShipping\Dao\ShippingRateDao;
use AstralShipping\Exception\ShippingException;
use AstralShipping\Http\CurlHttpClient;
use AstralShipping\Method\ColissimoMethod;
use AstralShipping\Method\FlatRateMethod;
use AstralShipping\Method\MondialRelayMethod;
use AstralShipping\Method\SendcloudMethod;
use AstralShipping\Method\StorePickupMethod;
use AstralShipping\Rate\ArrayRateTable;
use AstralShipping\Service\ShippingCalculator;
use AstralShipping\Service\ShippingService;
use Core\Container;
use Core\ServiceProviderInterface;

/**
 * Enregistre le package dans le conteneur Astral.
 * Les méthodes actives sont listées EXPLICITEMENT dans config/app.php → 'shipping' → 'methods'.
 */
final class ShippingServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container, array $appConfig, array $dbConfig): void
    {
        /** @var array<string, mixed> $cfg */
        $cfg = $appConfig['shipping'] ?? [];

        $container->singleton(ShippingRateDao::class, fn(Container $c) => new ShippingRateDao($c->make(\PDO::class)));
        $container->singleton(OrderShipmentDao::class, fn(Container $c) => new OrderShipmentDao($c->make(\PDO::class)));

        $container->singleton(
            RateLookupInterface::class,
            fn(Container $c): RateLookupInterface => ($cfg['rates_source'] ?? 'database') === 'config'
                ? new ArrayRateTable($cfg['rates'] ?? [])
                : $c->make(ShippingRateDao::class)
        );

        $container->singleton(
            HttpClientInterface::class,
            fn(): HttpClientInterface => new CurlHttpClient((int) ($cfg['http_timeout'] ?? 10))
        );

        $container->singleton(ShippingCalculator::class, fn(Container $c) => self::buildCalculator($c, $cfg));
        $container->singleton(ShippingService::class, fn(Container $c) => new ShippingService(
            $c->make(ShippingCalculator::class)
        ));
    }

    /** @param array<string, mixed> $cfg */
    private static function buildCalculator(Container $c, array $cfg): ShippingCalculator
    {
        $logger = $c->make(\Core\Logger::class);
        $calculator = new ShippingCalculator(
            static fn(ShippingMethodInterface $method, ShippingException $e) => $logger->warning(
                'Méthode de livraison indisponible',
                ['method' => $method->code(), 'error' => $e->getMessage()]
            )
        );

        $free  = isset($cfg['free_shipping_threshold_cents']) ? (int) $cfg['free_shipping_threshold_cents'] : null;
        $rates = $c->make(RateLookupInterface::class);

        foreach ($cfg['methods'] ?? ['flat_rate'] as $name) {
            $calculator->register(match ($name) {
                'flat_rate' => new FlatRateMethod(
                    priceCents: (int) ($cfg['flat_rate']['price_cents'] ?? 590),
                    label: (string) ($cfg['flat_rate']['label'] ?? 'Livraison standard'),
                    countries: (array) ($cfg['flat_rate']['countries'] ?? []),
                    freeThresholdCents: $free,
                    estimatedDays: isset($cfg['flat_rate']['days']) ? (int) $cfg['flat_rate']['days'] : null,
                ),
                'store_pickup'       => new StorePickupMethod((string) ($cfg['store_pickup']['label'] ?? 'Retrait en boutique')),
                'colissimo_home'     => new ColissimoMethod($rates, false, $free),
                'colissimo_pickup'   => new ColissimoMethod($rates, true, $free),
                'mondial_relay'      => new MondialRelayMethod($rates, true, $free),
                'mondial_relay_home' => new MondialRelayMethod($rates, false, $free),
                'sendcloud'          => self::sendcloud($c, $cfg),
                default              => throw new ShippingException("Méthode de livraison inconnue : {$name}"),
            });
        }

        return $calculator;
    }

    /** @param array<string, mixed> $cfg */
    private static function sendcloud(Container $c, array $cfg): SendcloudMethod
    {
        $cache = $c->make(\Core\Cache::class);

        return new SendcloudMethod(
            http: $c->make(HttpClientInterface::class),
            publicKey: (string) ($_ENV['SENDCLOUD_PUBLIC_KEY'] ?? ''),
            secretKey: (string) ($_ENV['SENDCLOUD_SECRET_KEY'] ?? ''),
            remember: static fn(string $key, int $ttl, callable $producer) => $cache->remember($key, $ttl, $producer),
            cacheTtlSeconds: (int) ($cfg['sendcloud']['cache_ttl'] ?? 3600),
            markupCents: (int) ($cfg['sendcloud']['markup_cents'] ?? 0),
            allowedCarriers: $cfg['sendcloud']['carriers'] ?? null,
        );
    }
}
