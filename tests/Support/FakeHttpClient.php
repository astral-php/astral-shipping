<?php

declare(strict_types=1);

namespace AstralShipping\Tests\Support;

use AstralShipping\Contract\HttpClientInterface;

/** Client HTTP factice : renvoie une réponse prédéfinie et compte les appels. */
final class FakeHttpClient implements HttpClientInterface
{
    public int $calls = 0;
    /** @var array<string, string> */
    public array $lastHeaders = [];

    public function __construct(private int $status, private string $body)
    {
    }

    public function request(string $method, string $url, array $headers = [], ?string $body = null): array
    {
        $this->calls++;
        $this->lastHeaders = $headers;

        return ['status' => $this->status, 'body' => $this->body];
    }
}
