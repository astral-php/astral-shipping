# Intégration au tunnel de commande (astral-shop + astral-payment)

## Flux

```
Panier → adresse → [offres de livraison] → choix → total (panier + port) → paiement → commande + snapshot
```

1. **Adresse saisie** : construire un `ShippingContext` (sous-total en centimes, poids total en grammes, `Address`).
2. **Affichage** : `$shipping->quotes($context)` → partial `shipping/options`.
3. **Validation** (POST) : `$shipping->select(...)` — lève `InvalidSelectionException` si l'offre n'existe plus ou si le point relais manque.
4. **Paiement** : `$shipping->total($subtotal, $selection)` est le montant à transmettre à `astral-payment` (PaymentIntent). **Ne jamais** reprendre un prix envoyé par le navigateur.
5. **Après paiement confirmé** : `OrderShipmentDao::record($orderId, $selection)`.

Stocke la `ShippingSelection` (ou son `code` + point relais) en session entre les étapes, puis **recalcule** à la validation finale : le panier ou l'adresse a pu changer.

## Exemple de contrôleur

Voir [`examples/checkout-integration.php`](../examples/checkout-integration.php).

## Routes

```php
$router->group('/checkout', function (Router $r): void {
    $r->get('/shipping',  CheckoutController::class, 'shipping');
    $r->post('/shipping', CheckoutController::class, 'saveShipping');
}, [AuthMiddleware::class, CsrfMiddleware::class]);
```

## Événement (optionnel)

`ShippingMethodSelected` est une classe simple, indépendante du core. Pour l'utiliser avec `EventDispatcher`, crée dans ton application un événement qui implémente `EventInterface` et passe une closure à `ShippingService` :

```php
$container->singleton(ShippingService::class, fn(Container $c) => new ShippingService(
    $c->make(ShippingCalculator::class),
    fn(\AstralShipping\Events\ShippingMethodSelected $e) =>
        $c->make(EventDispatcher::class)->dispatch(new App\Events\ShippingChosen($e->selection)),
));
```

(Redéclarer `ShippingService::class` dans ton `AppServiceProvider`, chargé après `ShippingServiceProvider`.)

## Poids et sous-total

`ShippingContext::fromLines()` additionne les lignes du panier :

```php
$context = ShippingContext::fromLines(
    array_map(fn($l) => [
        'weight_g'    => $l->product->weight_g,
        'price_cents' => $l->product->price_cents,
        'quantity'    => $l->quantity,
    ], $cart->lines()),
    new Address($country, $postalCode, $city),
);
```

Pense à ajouter une colonne `weight_g` (entier) à tes produits si elle n'existe pas.

## Sécurité

- Le client n'envoie que : `shipping_method` (code), `pickup_point_id`, `pickup_point_label`.
- CSRF : `CsrfMiddleware` sur les routes POST, `<?= $csrf->field() ?>` dans le formulaire.
- Échappe toujours le libellé du point relais à l'affichage (`htmlspecialchars`).
