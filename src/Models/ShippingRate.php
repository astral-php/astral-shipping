<?php

declare(strict_types=1);

namespace AstralShipping\Models;

/** Ligne de la table `shipping_rates` (modèle anémique, propriétés snake_case comme les modèles Astral). */
class ShippingRate
{
    public int $id = 0;
    public string $method_code = '';
    public string $country_code = '*';
    public int $min_weight_g = 0;
    public int $max_weight_g = 0;
    public int $price_cents = 0;
    public ?int $estimated_days = null;
    public int $active = 1;
}
