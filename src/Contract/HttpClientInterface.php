<?php

declare(strict_types=1);

namespace AstralShipping\Contract;

/** Client HTTP minimal : permet de tester les méthodes API sans réseau. */
interface HttpClientInterface
{
    /**
     * @param array<string, string> $headers
     * @return array{status:int, body:string}
     * @throws \AstralShipping\Exception\ShippingException en cas d'échec réseau
     */
    public function request(string $method, string $url, array $headers = [], ?string $body = null): array;
}
