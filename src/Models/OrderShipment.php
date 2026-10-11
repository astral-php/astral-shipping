<?php

declare(strict_types=1);

namespace AstralShipping\Models;

/** Ligne de la table `order_shipments` : snapshot du mode de livraison d'une commande. */
class OrderShipment
{
    public int $id = 0;
    public int $order_id = 0;
    public string $method_code = '';
    public string $carrier = '';
    public string $label = '';
    public int $price_cents = 0;
    public string $currency = 'EUR';
    public ?string $pickup_point_id = null;
    public ?string $pickup_point_label = null;
    public ?string $tracking_number = null;
    public string $status = 'pending';
    public string $created_at = '';
}
