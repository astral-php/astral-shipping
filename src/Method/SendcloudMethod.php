<?php

declare(strict_types=1);

namespace AstralShipping\Method;

use AstralShipping\Contract\HttpClientInterface;
use AstralShipping\Contract\ShippingMethodInterface;
use AstralShipping\Exception\ShippingException;
use AstralShipping\Models\ShippingContext;
use AstralShipping\Models\ShippingQuote;

/**
 * « API globale » : offres multi-transporteurs fournies par Sendcloud.
 *
 * Utilise GET /api/v2/shipping_methods (Basic auth : clé publique / clé secrète).
 * Sendcloud migre ses intégrations vers l'API v3 : pour une autre version ou un autre
 * agrégateur (Boxtal, ShipEngine…), implémente ShippingMethodInterface en t'inspirant de cette classe.
 */
final class SendcloudMethod implements ShippingMethodInterface
{
    private const ENDPOINT = 'https://panel.sendcloud.sc/api/v2/shipping_methods';

    /**
     * @param ?\Closure $remember  fn(string $key, int $ttl, callable $producer): mixed — ex. un wrapper de Core\Cache::remember
     * @param list<string>|null $allowedCarriers Filtre sur le champ "carrier" (ex. ['colissimo', 'mondial_relay'])
     */
    public function __construct(
        private HttpClientInterface $http,
        private string $publicKey,
        private string $secretKey,
        private ?\Closure $remember = null,
        private int $cacheTtlSeconds = 3600,
        private int $markupCents = 0,
        private ?array $allowedCarriers = null,
    ) {
    }

    public function code(): string
    {
        return 'sendcloud';
    }

    public function label(): string
    {
        return 'Sendcloud';
    }

    public function quotes(ShippingContext $context): array
    {
        $country = $context->destination->country;
        $kg      = $context->weightGrams / 1000;
        $quotes  = [];

        foreach ($this->fetchMethods() as $method) {
            if (!is_array($method)) {
                continue;
            }
            $carrier = (string) ($method['carrier'] ?? '');
            if ($this->allowedCarriers !== null && !in_array($carrier, $this->allowedCarriers, true)) {
                continue;
            }
            if ($kg < (float) ($method['min_weight'] ?? 0) || $kg > (float) ($method['max_weight'] ?? PHP_INT_MAX)) {
                continue;
            }

            foreach ((array) ($method['countries'] ?? []) as $entry) {
                if (!is_array($entry) || strtoupper((string) ($entry['iso_2'] ?? '')) !== $country) {
                    continue;
                }
                if (!isset($entry['price'])) {
                    continue; // tarif non disponible pour ce pays
                }

                $hours    = isset($entry['lead_time_hours']) ? (int) $entry['lead_time_hours'] : 0;
                $quotes[] = new ShippingQuote(
                    code: 'sendcloud:' . $method['id'],
                    label: (string) ($method['name'] ?? 'Sendcloud'),
                    carrier: $carrier,
                    priceCents: (int) round(((float) $entry['price']) * 100) + $this->markupCents,
                    currency: $context->currency,
                    estimatedDays: $hours > 0 ? (int) ceil($hours / 24) : null,
                    requiresPickupPoint: ($method['service_point_input'] ?? 'none') !== 'none',
                    meta: ['sendcloud_method_id' => $method['id']],
                );
            }
        }

        return $quotes;
    }

    /** @return list<array<string, mixed>> */
    private function fetchMethods(): array
    {
        $producer = fn(): array => $this->callApi();

        if ($this->remember === null) {
            return $producer();
        }

        $result = ($this->remember)('astral_shipping.sendcloud.methods', $this->cacheTtlSeconds, $producer);

        return is_array($result) ? $result : [];
    }

    /** @return list<array<string, mixed>> */
    private function callApi(): array
    {
        $response = $this->http->request('GET', self::ENDPOINT, [
            'Authorization' => 'Basic ' . base64_encode($this->publicKey . ':' . $this->secretKey),
            'Accept'        => 'application/json',
        ]);

        if ($response['status'] !== 200) {
            throw new ShippingException('Sendcloud a répondu HTTP ' . $response['status'] . '.');
        }

        $data = json_decode($response['body'], true);
        if (!is_array($data) || !isset($data['shipping_methods']) || !is_array($data['shipping_methods'])) {
            throw new ShippingException('Réponse Sendcloud inattendue.');
        }

        return array_values($data['shipping_methods']);
    }
}
