<?php

declare(strict_types=1);

namespace AstralShipping\Console;

use AstralShipping\Dao\ShippingRateDao;
use AstralShipping\Rate\CsvRateParser;
use Core\Console\CommandInterface;
use Core\Console\Console;

/**
 * php bin/console shipping:rates:import <fichier.csv> [--replace]
 *
 * Colonnes (avec en-tête) : method_code,country,min_weight_g,max_weight_g,price_eur,estimated_days
 */
final class ImportRatesCommand implements CommandInterface
{
    public function __construct(
        private ShippingRateDao $rates,
        private \PDO $pdo,
        private CsvRateParser $parser = new CsvRateParser(),
    ) {
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

        try {
            $rows = $this->parser->parseFile($file);
        } catch (\InvalidArgumentException $e) {
            echo $e->getMessage() . "\n";

            return 1;
        }

        if ($rows === []) {
            echo "Aucune ligne de tarif à importer.\n";

            return 1;
        }

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
