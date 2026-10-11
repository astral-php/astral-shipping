<?php

declare(strict_types=1);

namespace AstralShipping\Http;

use AstralShipping\Contract\HttpClientInterface;
use AstralShipping\Exception\ShippingException;

/** Implémentation par défaut (ext-curl), sans dépendance Composer. */
final class CurlHttpClient implements HttpClientInterface
{
    public function __construct(private int $timeoutSeconds = 10)
    {
    }

    public function request(string $method, string $url, array $headers = [], ?string $body = null): array
    {
        if (!function_exists('curl_init')) {
            throw new ShippingException('ext-curl est requise pour CurlHttpClient.');
        }

        $lines = [];
        foreach ($headers as $name => $value) {
            $lines[] = $name . ': ' . $value;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => strtoupper($method),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $lines,
            CURLOPT_TIMEOUT        => $this->timeoutSeconds,
            CURLOPT_CONNECTTIMEOUT => min(5, $this->timeoutSeconds),
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $response = curl_exec($ch);
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new ShippingException('Requête HTTP échouée : ' . $error);
        }

        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        return ['status' => $status, 'body' => (string) $response];
    }
}
