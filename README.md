# astral-php/astral-shipping

Frais de port **multi-options** pour [Astral](https://github.com/astral-php) : forfait, retrait boutique, barèmes pays × poids (Colissimo / Mondial Relay), API agrégateur (Sendcloud).

[![PHP](https://img.shields.io/badge/PHP-8.1%2B-777BB4?logo=php&logoColor=white)](https://www.php.net)
[![astral-core](https://img.shields.io/badge/astral--core-%5E1.2-4f46e5)](https://packagist.org/packages/astral-php/astral-core)
[![Tests](https://img.shields.io/badge/tests-PHPUnit%2010%2B-9933CC)](./phpunit.xml)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

> **Statut : 0.5.0** — Colissimo + Mondial Relay **prêts prod barèmes**.  
> Philosophie Astral : **explicite, sans magie, opt-in** — tu déclares les méthodes actives, rien n’est découvert automatiquement.  
> Ce n’est **pas** une version 1.0 (agrégateur API) : voir [Limites](#limites-050).

## Compatibilité

| astral-core | PHP | astral-shipping |
| --- | --- | --- |
| ^1.2 | 8.1 → 8.4 | 0.x |

Le **domaine** (calcul des offres) n’a aucune dépendance au core : seuls le ServiceProvider, les DAOs et la commande console en ont.

## Limites (0.5.0)

| Sujet | Réalité |
| --- | --- |
| Colissimo / Mondial Relay | **Barèmes contrat** (`shipping_rates` / CSV) — pas d’API transporteur officielle |
| Grilles tarifaires | **Non incluses** (contrat ; CSV d’exemple fictif enrichi FR/BE/DE) |
| Points relais | `pickup_point_id` obligatoire côté serveur ; **widget** = guide [`docs/widgets-pickup.md`](docs/widgets-pickup.md) (hors package) |
| Sendcloud | API **v2** opt-in MVP — maturité cible **1.0.0** |
| Boutique | Shop profil standard = forfait + retrait ; profil Colissimo/MR = intégration shop (poids, widgets) |

## Installation

```bash
composer require astral-php/astral-shipping
```

Développement local (workspace `components-astral`) :

```json
"repositories": [{ "type": "path", "url": "../components-astral/astral-shipping", "options": { "symlink": true } }]
```

```bash
composer require astral-php/astral-shipping:@dev
```

### 1. ServiceProvider — `config/dependencies.php`

```php
return [
    // …
    AstralShipping\ShippingServiceProvider::class,
];
```

### 2. Migrations

Copie `stubs/database/migrations/*` vers `database/migrations/` du projet hôte, puis :

```bash
php bin/console migrate
```

| Table | Rôle |
| --- | --- |
| `shipping_rates` | Barèmes : méthode × pays × tranche de poids (centimes) |
| `order_shipments` | Snapshot du mode de livraison de chaque commande |

### 3. Variables d'environnement (si `sendcloud` est activé)

Copier `stubs/.env.shipping.example` dans ton `.env`.

### 4. Configuration — `config/app.php`

Modèle : `stubs/config/shipping.example.php`.

**MVP boutique (recommandé pour démarrer) :**

```php
'shipping' => [
    'methods' => ['flat_rate', 'store_pickup'],
    'free_shipping_threshold_cents' => 6000,
    'rates_source' => 'config',
    'http_timeout' => 10,
    'flat_rate' => [
        'price_cents' => 590,
        'label'       => 'Livraison standard',
        'countries'   => ['FR'],
        'days'        => 4,
    ],
    'store_pickup' => ['label' => 'Retrait en boutique'],
    'rates' => [],
],
```

**Profil Colissimo + Mondial Relay (0.5.0)** — activer explicitement + importer les barèmes :

```php
'methods' => [
    'flat_rate', 'store_pickup',
    'colissimo_home', 'colissimo_pickup', 'mondial_relay',
],
'rates_source' => 'database', // php bin/console shipping:rates:import … --replace
```

Widgets relais : [`docs/widgets-pickup.md`](docs/widgets-pickup.md). Sendcloud reste opt-in (voir stub config).

## Les méthodes disponibles

| Code | Classe | Source du tarif | Point relais |
| --- | --- | --- | --- |
| `flat_rate` | `FlatRateMethod` | Forfait (config) | Non |
| `store_pickup` | `StorePickupMethod` | Gratuit | Non |
| `colissimo_home` | `ColissimoMethod` | Barème `shipping_rates` | Non |
| `colissimo_pickup` | `ColissimoMethod(pickup: true)` | Barème | **Oui** |
| `mondial_relay` | `MondialRelayMethod` | Barème | **Oui** |
| `mondial_relay_home` | `MondialRelayMethod(relay: false)` | Barème | Non |
| `sendcloud` | `SendcloudMethod` | API Sendcloud (N offres, cache) | Selon l'offre |
| *(la tienne)* | `ShippingMethodInterface` | À toi de voir | — |

Pourquoi des barèmes pour Colissimo et Mondial Relay ? Parce que **tes tarifs dépendent de ton contrat** : on les saisit (ou importe en CSV) une fois, le calcul reste instantané, hors-ligne et testable. Voir [`docs/colissimo-mondial-relay.md`](docs/colissimo-mondial-relay.md) et [`docs/widgets-pickup.md`](docs/widgets-pickup.md).

## Utilisation

```php
use AstralShipping\Models\{Address, ShippingContext};
use AstralShipping\Service\ShippingService;

// 1. Contexte : montants en CENTIMES, poids en GRAMMES
$context = new ShippingContext(
    subtotalCents: 4990,
    weightGrams: 1200,
    destination: new Address('FR', '75001', 'Paris'),
);

// 2. Lister les offres (triées par prix)
$quotes = $shipping->quotes($context);

// 3. Valider le choix du client — le prix est TOUJOURS recalculé côté serveur
$selection = $shipping->select($context, $_POST['shipping_method'], $_POST['pickup_point_id'] ?? null, $_POST['pickup_point_label'] ?? null);

// 4. Total à encaisser (→ astral-payment)
$total = $shipping->total($context->subtotalCents, $selection);

// 5. À la création de la commande : snapshot
$shipmentDao->record($orderId, $selection);
```

Vue : `stubs/views/shipping/options.php` → `views/partials/`. Exemple de contrôleur : [`examples/checkout-integration.php`](examples/checkout-integration.php) et [`docs/checkout-integration.md`](docs/checkout-integration.md).

## Barèmes

### En base (recommandé)

Import CSV :

```bash
php bin/console shipping:rates:import database/rates.csv --replace
```

Colonnes : `method_code,country,min_weight_g,max_weight_g,price_eur,estimated_days`. Voir [`stubs/database/rates.example.csv`](stubs/database/rates.example.csv) (valeurs **fictives**).

Règles : poids en grammes, **bornes inclusives** (`0–500` puis `501–1000`), pays exact prioritaire sur `*`, à égalité le tarif le plus bas.

### En config (sans base)

```php
'rates_source' => 'config',
'rates' => [
    ['method' => 'colissimo_home', 'country' => 'FR', 'min_g' => 0, 'max_g' => 500, 'price_cents' => 495, 'days' => 2],
],
```

## Ajouter un transporteur

Implémente `ShippingMethodInterface` (3 méthodes) et enregistre-la dans ton `AppServiceProvider`. Guide : [`docs/extending.md`](docs/extending.md).

## Principes de conception

- **Explicite** : méthodes déclarées une à une, pas d'auto-découverte, pas de lazy-loading.
- **Centimes et grammes** (`int`), jamais de `float` pour l'argent.
- **Serveur maître** : le client envoie un *code*, jamais un prix.
- **Pannes isolées** : si l'API d'un transporteur tombe, ses offres disparaissent, les autres restent (erreur loguée via `Core\Logger`).
- **Snapshot** : le port d'une commande est figé dans `order_shipments`.
- **Opt-in** : le cœur d'Astral n'est pas modifié ; tout vit dans `app/` + ce package.

## Structure

```
src/
  ShippingServiceProvider.php   # câblage conteneur (méthodes lues dans config)
  Contract/                     # ShippingMethodInterface, RateLookupInterface, HttpClientInterface
  Method/                       # FlatRate, StorePickup, TableRate, Colissimo, MondialRelay, Sendcloud
  Rate/ArrayRateTable.php       # barème en mémoire (config / tests)
  Rate/CsvRateParser.php        # parsing CSV (commande import + tests)
  Dao/                          # ShippingRateDao, OrderShipmentDao (AbstractDao)
  Service/                      # ShippingCalculator, ShippingService
  Models/                       # Address, ShippingContext, ShippingQuote, Rate, PickupPoint, ShippingSelection…
  Events/ShippingMethodSelected # événement optionnel
  Http/CurlHttpClient.php       # client HTTP par défaut
  Console/ImportRatesCommand.php
  Exception/
stubs/
  .env.shipping.example
  config/shipping.example.php
  database/migrations/
  database/rates.example.csv
  views/shipping/options.php
```

## Tests

```bash
composer install
composer test
```

La suite couvre le calcul (forfait, seuil de gratuité, barèmes, priorité pays), l’import CSV (`CsvRateParser`), `SendcloudMethod` (client HTTP factice, cache, filtres), le calculateur (tri, doublons, isolation des pannes) et la sélection (point relais obligatoire, prix recalculé).

## Licence

MIT
