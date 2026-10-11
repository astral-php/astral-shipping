<?php

declare(strict_types=1);

namespace AstralShipping\Dao;

use AstralShipping\Models\OrderShipment;
use AstralShipping\Models\ShippingSelection;
use Database\AbstractDao;

/** Snapshot du mode de livraison choisi pour une commande (jamais recalculé après coup). */
final class OrderShipmentDao extends AbstractDao
{
    public function __construct(private \PDO $db)
    {
        parent::__construct($db);
    }

    protected function getTable(): string
    {
        return 'order_shipments';
    }

    protected function getModelClass(): string
    {
        return OrderShipment::class;
    }

    /** Enregistre le choix validé ; retourne l'id de la ligne. */
    public function record(int $orderId, ShippingSelection $selection): int
    {
        $quote = $selection->quote;
        $point = $selection->pickupPoint;

        return (int) $this->insert([
            'order_id'           => $orderId,
            'method_code'        => $quote->code,
            'carrier'            => $quote->carrier,
            'label'              => $quote->label,
            'price_cents'        => $quote->priceCents,
            'currency'           => $quote->currency,
            'pickup_point_id'    => $point?->id,
            'pickup_point_label' => $point?->label,
            'status'             => 'pending',
            'created_at'         => date('Y-m-d H:i:s'),
        ]);
    }

    /** @return array<string, mixed>|null */
    public function findByOrder(int $orderId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM order_shipments WHERE order_id = :id LIMIT 1');
        $stmt->execute(['id' => $orderId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    public function setTracking(int $orderId, string $trackingNumber, string $status = 'shipped'): void
    {
        $stmt = $this->db->prepare(
            'UPDATE order_shipments SET tracking_number = :t, status = :s WHERE order_id = :id'
        );
        $stmt->execute(['t' => $trackingNumber, 's' => $status, 'id' => $orderId]);
    }
}
