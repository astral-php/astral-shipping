<?php

declare(strict_types=1);

namespace AstralShipping\Console;

use AstralShipping\Dao\ShippingRateDao;
use Core\Console\CommandInterface;
use Core\Console\Console;

/**
 * php bin/console shipping:rates:import <fichier.csv> [--replace]
 *
 * Colonnes (avec en-tête) : method_code,country,min_weight_g,max_weight_g,price_eur,estimated_days
 */
final class ImportRatesCommand implements CommandInterface
{
    public function __construct(private ShippingRateDao $rates, private \PDO $pdo)
    {
    }

    public function getName(): string
    {
        return 'shipping:rates:import';
    }

    public function getDescription(): string
    {
        return 'Importe un barème de frais de port depuis un CSV';
    }

    public function execute(array $args, Console $console): int
    {
        $file    = $args[0] ?? '';
        $replace = in_array('--replace', $args, true);

        if ($file === '' || !is_readable($file)) {
            echo "Usage : php bin/console shipping:rates:import <fichier.csv> [--replace]\n";
            return 1;
        }

        $handle = fopen($file, 'rb');
        $header = fgetcsv($handle, 0, ',', '"', '');
        $expected = ['method_code', 'country', 'min_weight_g', 'max_weight_g', 'price_eur', 'estimated_days'];
        if ($header === false || array_slice($header, 0, 5) !== array_slice($expected, 0, 5)) {
            fclose($handle);
            echo "En-tête CSV invalide. Attendu : " . implode(',', $expected) . "\n";
            return 1;
        }

        $rows = [];
        while (($line = fgetcsv($handle, 0, ',', '"', '')) !== false) {
            if ($line === [null] || count($line) < 5) {
                continue;
            }
            $rows[] = [
                'method_code'    => trim($line[0]),
                'country_code'   => strtoupper(trim($line[1])) ?: '*',
                'min_weight_g'   => (int) $line[2],
                'max_weight_g'   => (int) $line[3],
                'price_cents'    => (int) round((float) str_replace(',', '.', $line[4]) * 100),
                'estimated_days' => isset($line[5]) && $line[5] !== '' ? (int) $line[5] : null,
                'active'         => 1,
            ];
        }
        fclose($handle);

        $this->pdo->beginTransaction();
        try {
            if ($replace) {
                foreach (array_unique(array_column($rows, 'method_code')) as $code) {
                    $stmt = $this->pdo->prepare('DELETE FROM shipping_rates WHERE method_code = :c');
                    $stmt->execute(['c' => $code]);
                }
            }
            foreach ($rows as $row) {
                $this->rates->insert($row);
            }
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            echo 'Import annulé : ' . $e->getMessage() . "\n";
            return 1;
        }

        $console->success(count($rows) . ' tarif(s) importé(s).');
        return 0;
    }
}
