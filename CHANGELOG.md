# Changelog

Toutes les versions notables de ce projet sont documentées ici.

Le format s’inspire de [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/),
et ce projet adhère au [Versionnage Sémantique](https://semver.org/lang/fr/).

## [0.1.0] — 2026-10-11

### Ajouté
- Structure alignée sur `astral-payment` : namespace `AstralShipping\`, `src/` à plat, dossier `stubs/` (migrations, vue, config, .env), dépendance `astral-php/astral-core ^1.2`.
- `ShippingCalculator` : enregistrement explicite des méthodes, devis triés par prix, résilience aux pannes d'API.
- `ShippingService` : sélection validée côté serveur (prix toujours recalculé, point relais obligatoire si requis).
- Méthodes : `FlatRateMethod`, `StorePickupMethod`, `TableRateMethod`, `ColissimoMethod`, `MondialRelayMethod`, `SendcloudMethod`.
- Barèmes pays × tranches de poids : `ShippingRateDao` (base) ou `ArrayRateTable` (config).
- `OrderShipmentDao` : snapshot du mode de livraison dans la commande.
- Migrations SQLite + MySQL (dans `stubs/database/migrations/`) : `shipping_rates`, `order_shipments`.
- Commande `shipping:rates:import` (CSV).
- Partial de vue Tailwind `stubs/views/shipping/options.php`.
- Documentation (`docs/`) et exemples (`examples/`).
- Suite PHPUnit : 25 tests (forfait, franco, barèmes, Sendcloud mock, sélection, pannes).

### Notes de publication
- Première version Packagist **MVP** — pas une 1.0.
- Colissimo / Mondial Relay = barèmes contrat (pas d’API transporteur) ; widgets relais hors package.
- README : section Limites MVP + config recommandée `flat_rate` + `store_pickup`.
