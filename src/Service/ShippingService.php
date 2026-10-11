<?php

declare(strict_types=1);

namespace AstralShipping\Service;

use AstralShipping\Events\ShippingMethodSelected;
use AstralShipping\Exception\InvalidSelectionException;
use AstralShipping\Models\PickupPoint;
use AstralShipping\Models\ShippingContext;
use AstralShipping\Models\ShippingQuote;
use AstralShipping\Models\ShippingSelection;

/** Point d'entrée pour le tunnel de commande : lister les offres, valider le choix, calculer le total. */
final class ShippingService
{
    /**
     * @param ?\Closure $dispatch fn(ShippingMethodSelected $event): void (optionnel)
     */
    public function __construct(
        private ShippingCalculator $calculator,
        private ?\Closure $dispatch = null,
    ) {
    }

    /** @return list<ShippingQuote> */
    public function quotes(ShippingContext $context): array
    {
        return $this->calculator->quotes($context);
    }

    /**
     * Valide le choix du client. Le prix est TOUJOURS recalculé côté serveur.
     *
     * @throws InvalidSelectionException
     */
    public function select(
        ShippingContext $context,
        string $code,
        ?string $pickupPointId = null,
        ?string $pickupPointLabel = null,
    ): ShippingSelection {
        $quote = $this->calculator->find($code, $context);
        if ($quote === null) {
            throw new InvalidSelectionException("Mode de livraison indisponible : {$code}");
        }

        $point = null;
        if ($quote->requiresPickupPoint) {
            $id = trim((string) $pickupPointId);
            if ($id === '') {
                throw new InvalidSelectionException('Veuillez choisir un point relais.');
            }
            $point = new PickupPoint($id, trim((string) $pickupPointLabel), $quote->carrier);
        }

        $selection = new ShippingSelection($quote, $point);
        if ($this->dispatch !== null) {
            ($this->dispatch)(new ShippingMethodSelected($selection));
        }

        return $selection;
    }

    /** Total à encaisser (centimes) : panier + frais de port. À transmettre à astral-payment. */
    public function total(int $subtotalCents, ShippingSelection $selection): int
    {
        return $subtotalCents + $selection->quote->priceCents;
    }
}
