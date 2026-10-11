# Colissimo & Mondial Relay

## Principe

Les deux transporteurs sont gérés par **barèmes** (`TableRateMethod`) :

1. Tu saisis la grille tarifaire de **ton contrat** (poids × pays) dans `shipping_rates`.
2. `ColissimoMethod` / `MondialRelayMethod` cherchent le tarif applicable au panier.
3. Pour les offres « point relais », le client choisit son point via le **widget du transporteur** ; son identifiant est posté dans `pickup_point_id`.

Le package **ne contient aucun tarif officiel** : ils changent chaque année et dépendent de ton contrat. Les valeurs de [`examples/rates.csv`](../stubs/database/rates.example.csv) sont fictives.

## Codes de méthode

| Offre | `method_code` à utiliser dans le CSV / la base |
| --- | --- |
| Colissimo à domicile | `colissimo_home` |
| Colissimo point de retrait | `colissimo_pickup` |
| Mondial Relay point relais | `mondial_relay` |
| Mondial Relay à domicile | `mondial_relay_home` |

## Remplir les barèmes

```csv
method_code,country,min_weight_g,max_weight_g,price_eur,estimated_days
colissimo_home,FR,0,500,5.20,2
colissimo_home,FR,501,1000,6.80,2
```

```bash
php bin/console shipping:rates:import database/rates.csv --replace
```

`--replace` supprime d'abord les lignes existantes des `method_code` présents dans le fichier.

Enregistrer la commande dans `bin/console` :

```php
use AstralShipping\Console\ImportRatesCommand;
use AstralShipping\Dao\ShippingRateDao;

$console->register(new ImportRatesCommand(
    $container->make(ShippingRateDao::class),
    $container->make(\PDO::class),
));
```

## Choisir un point relais (côté navigateur)

Le serveur ne vérifie pas l'existence du point : il exige seulement un `pickup_point_id` non vide et le stocke avec son libellé. Le choix se fait avec le **widget officiel** de chaque transporteur (documentation Colissimo / Mondial Relay, qui peut nécessiter un identifiant d'enseigne ou un jeton selon ton contrat).

Le partial `stubs/views/shipping/options.php` fournit les champs cachés à remplir :

```html
<input type="hidden" name="pickup_point_id"    id="pickup_point_id">
<input type="hidden" name="pickup_point_label" id="pickup_point_label">
<div id="pickup-widget"></div>
```

Dans le callback « point sélectionné » du widget, tu écris simplement :

```js
document.getElementById('pickup_point_id').value    = point.id;     // nom de champ selon le widget
document.getElementById('pickup_point_label').value = point.label;
```

Et tu n'affiches le widget que si le bouton radio coché a `data-requires-pickup="1"` (déjà présent dans le partial). Adapte les paramètres du widget à la documentation du transporteur : ils ne sont volontairement pas codés ici.

## Port offert

`free_shipping_threshold_cents` dans la config s'applique aux offres de barème et au forfait (pas à Sendcloud, dont les prix viennent de l'API).
