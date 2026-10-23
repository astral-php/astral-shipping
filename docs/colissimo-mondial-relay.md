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

## Limites transporteur (barèmes CSV — pas de constante PHP)

Le package **n’encode pas** ces plafonds en dur : tu les respectes via `max_weight_g`.  
Au-delà de la dernière tranche → **aucune offre** (comportement voulu).  
À vérifier périodiquement : les règles transporteur évoluent.

Astral Shipping calcule le port sur le **poids panier** (`weight_g`), pas sur le volume.  
Le contrôle dimensions reste **marchand / entrepôt / widget** tant que les produits n’exposent pas L×l×h.

### Colissimo

#### Poids

| Mode | Poids max | `method_code` / note CSV |
| --- | --- | --- |
| **Domicile** (et dépôt bureau / point de contact postal) | **30 kg** | `colissimo_home` → dernière tranche ≤ **30000** |
| Dépôt **boîte aux lettres** (étiquette en ligne) | **5 kg** | Cas métier spécifique — grille ou process à part (pas le défaut CSV) |
| **Point de retrait** — relais commerçants / consignes Pickup | **20 kg** | `colissimo_pickup` → dernière tranche ≤ **20000** (défaut exemple) |
| Point de retrait **bureau de poste** | jusqu’à **30 kg** | Optionnel : grille dédiée 20001–30000 si tu proposes ce canal |

#### Dimensions

| Règle | Limite |
| --- | --- |
| Développé (L + l + h) | **150 cm** max (jusqu’à **200 cm** avec supplément « volumineux » selon offre) |
| Plus long côté | **100 cm** max |

### Mondial Relay

> Source : [FAQ Mondial Relay — taille / poids max](https://www.mondialrelay.fr/faq-pro/envoyer-un-colis/quelle-est-la-taille-maximale-pour-envoyer-un-colis/)

#### Poids

| Mode / destination | Poids max |
| --- | --- |
| Standard (Point Relais **ou** Domicile) | **30 kg** (`max_weight_g` ≤ **30000**) |
| Lockers (consignes automatiques) | **25 kg** |
| Italie et Pologne | **25 kg** |

Pour lockers / IT / PL à 25 kg : soit une grille séparée, soit `max_weight_g = 25000` sur les lignes concernées.

#### Dimensions

| Règle | Limite |
| --- | --- |
| Développé (L + l + h) | **150 cm** max |
| Plus long côté | **120 cm** max |
| Lockers / Italie / Pologne | **64 × 41 × 38 cm** max |

## Port offert

`free_shipping_threshold_cents` dans la config s'applique aux offres de barème et au forfait (pas à Sendcloud, dont les prix viennent de l'API).
