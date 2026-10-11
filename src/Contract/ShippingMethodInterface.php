<?php

declare(strict_types=1);

namespace AstralShipping\Contract;

use AstralShipping\Exception\ShippingException;
use AstralShipping\Models\ShippingContext;
use AstralShipping\Models\ShippingQuote;

/**
 * Une famille d'offres de livraison.
 * Retourne 0 offre (non applicable), 1 offre (forfait, barème) ou N offres (agrégateur API).
 */
interface ShippingMethodInterface
{
    /** Identifiant de la méthode, unique dans le calculateur (ex. "colissimo_home"). */
    public function code(): string;

    public function label(): string;

    /**
     * @return list<ShippingQuote>
     * @throws ShippingException si la source de tarifs est indisponible (le calculateur l'isole).
     */
    public function quotes(ShippingContext $context): array;
}
