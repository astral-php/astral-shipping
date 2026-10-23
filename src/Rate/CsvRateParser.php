<?php

declare(strict_types=1);

namespace AstralShipping\Rate;

/**
 * Parse un CSV de barèmes (en-tête obligatoire).
 *
 * Colonnes : method_code,country,min_weight_g,max_weight_g,price_eur[,estimated_days]
 *
 * @phpstan-type RateRow array{
 *     method_code: string,
 *     country_code: string,
 *     min_weight_g: int,
 *     max_weight_g: int,
 *     price_cents: int,
 *     estimated_days: ?int,
 *     active: int
 * }
 */
final class CsvRateParser
{
    /** @var list<string> */
    public const HEADER = [
        'method_code',
        'country',
        'min_weight_g',
        'max_weight_g',
        'price_eur',
        'estimated_days',
    ];

    /**
     * @return list<RateRow>
     *
     * @throws \InvalidArgumentException en-tête invalide ou fichier illisible
     */
    public function parseFile(string $path): array
    {
        if ($path === '' || !is_readable($path)) {
            throw new \InvalidArgumentException('Fichier CSV introuvable ou illisible : ' . $path);
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new \InvalidArgumentException('Impossible d’ouvrir le CSV : ' . $path);
        }

        try {
            return $this->parseHandle($handle);
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param resource $handle
     *
     * @return list<RateRow>
     */
    public function parseHandle($handle): array
    {
        $header = fgetcsv($handle, 0, ',', '"', '');
        if ($header === false || !$this->headerMatches($header)) {
            throw new \InvalidArgumentException(
                'En-tête CSV invalide. Attendu : ' . implode(',', self::HEADER)
            );
        }

        $rows = [];
        while (($line = fgetcsv($handle, 0, ',', '"', '')) !== false) {
            if ($line === [null] || count($line) < 5) {
                continue;
            }

            $method = trim((string) $line[0]);
            if ($method === '' || str_starts_with($method, '#')) {
                continue;
            }

            $rows[] = [
                'method_code'    => $method,
                'country_code'   => strtoupper(trim((string) $line[1])) ?: '*',
                'min_weight_g'   => (int) $line[2],
                'max_weight_g'   => (int) $line[3],
                'price_cents'    => (int) round((float) str_replace(',', '.', (string) $line[4]) * 100),
                'estimated_days' => isset($line[5]) && $line[5] !== '' ? (int) $line[5] : null,
                'active'         => 1,
            ];
        }

        return $rows;
    }

    /** @param list<string|null> $header */
    private function headerMatches(array $header): bool
    {
        $normalized = array_map(
            static fn ($h): string => strtolower(trim((string) $h)),
            array_slice($header, 0, 5)
        );

        return $normalized === array_slice(self::HEADER, 0, 5);
    }
}
