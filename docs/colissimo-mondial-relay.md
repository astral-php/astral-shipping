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

Guide dédié (mapping champs, checklist boutique, MR + Colissimo) :
[`docs/widgets-pickup.md`](widgets-pickup.md).

Résumé : le serveur exige un `pickup_point_id` non vide (pas de contrôle d’existence API). Le partial `stubs/views/shipping/options.php` fournit les champs cachés + `data-requires-pickup` ; le widget officiel du transporteur remplit ces champs.

## Port offert

`free_shipping_threshold_cents` dans la config s'applique aux offres de barème et au forfait (pas à Sendcloud, dont les prix viennent de l'API).
