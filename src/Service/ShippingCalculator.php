<?php

declare(strict_types=1);

namespace AstralShipping\Service;

use AstralShipping\Contract\ShippingMethodInterface;
use AstralShipping\Exception\ShippingException;
use AstralShipping\Models\ShippingContext;
use AstralShipping\Models\ShippingQuote;

/**
 * Agrège les offres de toutes les méthodes enregistrées.
 * Enregistrement EXPLICITE (pas de découverte automatique) ; une méthode en panne est isolée.
 */
final class ShippingCalculator
{
    /** @var array<string, ShippingMethodInterface> */
    private array $methods = [];

    /**
     * @param ?\Closure $onError fn(ShippingMethodInterface $method, ShippingException $e): void — ex. log
     */
    public function __construct(private ?\Closure $onError = null)
    {
    }

    public function register(ShippingMethodInterface $method): self
    {
        if (isset($this->methods[$method->code()])) {
            throw new ShippingException("Méthode de livraison déjà enregistrée : {$method->code()}");
        }
        $this->methods[$method->code()] = $method;

        return $this;
    }

    /** @return array<string, ShippingMethodInterface> */
    public function methods(): array
    {
        return $this->methods;
    }

    /**
     * Toutes les offres applicables, de la moins chère à la plus chère.
     *
     * @return list<ShippingQuote>
     */
    public function quotes(ShippingContext $context): array
    {
        $all = [];
        foreach ($this->methods as $method) {
            try {
                foreach ($method->quotes($context) as $quote) {
                    $all[] = $quote;
                }
            } catch (ShippingException $e) {
                if ($this->onError !== null) {
                    ($this->onError)($method, $e);
                }
            }
        }

        usort($all, static fn(ShippingQuote $a, ShippingQuote $b): int
            => [$a->priceCents, $a->label] <=> [$b->priceCents, $b->label]);

        return $all;
    }

    /** Retrouve une offre par son code, recalculée pour CE panier (le prix client n'est jamais cru). */
    public function find(string $code, ShippingContext $context): ?ShippingQuote
    {
        foreach ($this->quotes($context) as $quote) {
            if ($quote->code === $code) {
                return $quote;
            }
        }

        return null;
    }

    public function cheapest(ShippingContext $context): ?ShippingQuote
    {
        return $this->quotes($context)[0] ?? null;
    }
}
