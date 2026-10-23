# Changelog

Toutes les versions notables de ce projet sont documentées ici.

Le format s’inspire de [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/),
et ce projet adhère au [Versionnage Sémantique](https://semver.org/lang/fr/).

## [0.5.1] — 2026-10-23

### Documenté
- Limites Mondial Relay (FAQ pro) : **30 kg** Point Relais/Domicile, **25 kg** Lockers / IT / PL ; dimensions (développé 150 cm, etc.).
- CSV d’exemple : tranches `mondial_relay` jusqu’à **30000 g** (prix fictifs).

## [0.5.0] — 2026-10-23

### Ajouté
- `CsvRateParser` : parsing CSV testable (virgule décimale, commentaires `#`, pays vide → `*`).
- Doc [`docs/widgets-pickup.md`](docs/widgets-pickup.md) — guide widgets Mondial Relay / Colissimo + checklist boutique.
- CSV d’exemple enrichi (FR / BE / DE, tranches étendues, `mondial_relay_home`).
- Profils documentés dans `stubs/config/shipping.example.php` (MVP / Colissimo+MR / Sendcloud).
- Tests : import CSV, edge cases barèmes (pays absent, seuil franco, pickup Colissimo, MR domicile).

### Modifié
- `ImportRatesCommand` délègue le parsing à `CsvRateParser`.
- README : statut **0.5.0** (barèmes Colissimo / MR matures) ; limites mises à jour.
- Lien widgets depuis `colissimo-mondial-relay.md` et le partial `options.php`.

### Notes de publication
- Tag **`0.5.0`** (sans `v`) — Colissimo + Mondial Relay **prêts prod barèmes**.
- Widgets JS et admin import restent hors package (shop).
- Agrégateur Sendcloud mature → cible **1.0.0** (après spike).

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
