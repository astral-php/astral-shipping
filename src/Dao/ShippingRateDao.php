<?php

declare(strict_types=1);

namespace AstralShipping\Dao;

use AstralShipping\Contract\RateLookupInterface;
use AstralShipping\Models\Rate;
use AstralShipping\Models\ShippingRate;
use Database\AbstractDao;

/** Barèmes en base : CRUD hérité d'AbstractDao + recherche du tarif applicable. */
final class ShippingRateDao extends AbstractDao implements RateLookupInterface
{
    public function __construct(private \PDO $db)
    {
        parent::__construct($db);
    }

    protected function getTable(): string
    {
        return 'shipping_rates';
    }

    protected function getModelClass(): string
    {
        return ShippingRate::class;
    }

    public function findRate(string $methodCode, string $country, int $weightGrams): ?Rate
    {
        // Pays exact avant joker '*', puis tarif le plus bas. Compatible SQLite / MySQL.
        $stmt = $this->db->prepare(
            'SELECT price_cents, estimated_days FROM shipping_rates
              WHERE method_code = :code AND active = 1
                AND country_code IN (:country, \'*\')
                AND min_weight_g <= :w1 AND max_weight_g >= :w2
              ORDER BY (country_code = \'*\') ASC, price_cents ASC
              LIMIT 1'
        );
        $stmt->execute([
            'code'    => $methodCode,
            'country' => strtoupper($country),
            'w1'      => $weightGrams,
            'w2'      => $weightGrams,
        ]);

        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($row === false) {
            return null;
        }

        return new Rate(
            (int) $row['price_cents'],
            $row['estimated_days'] !== null ? (int) $row['estimated_days'] : null,
        );
    }

    /** @return list<array<string, mixed>> Lignes brutes d'une méthode (pour une page d'admin). */
    public function listByMethod(string $methodCode): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM shipping_rates WHERE method_code = :code
              ORDER BY country_code, min_weight_g'
        );
        $stmt->execute(['code' => $methodCode]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
