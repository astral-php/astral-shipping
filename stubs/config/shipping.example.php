<?php

// Extrait de config/app.php — voir README pour le détail de chaque clé.

return [
    // …
    'shipping' => [
        // ── Profil A — MVP boutique (recommandé pour démarrer) ──────────────
        'methods' => ['flat_rate', 'store_pickup'],

        // ── Profil B — Colissimo + Mondial Relay (0.5.0, barèmes requis) ────
        // 'methods' => [
        //     'flat_rate',
        //     'store_pickup',
        //     'colissimo_home',
        //     'colissimo_pickup',
        //     'mondial_relay',
        //     // 'mondial_relay_home',
        // ],
        // 'rates_source' => 'database', // + shipping:rates:import stubs/database/rates.example.csv

        // ── Profil C — + agrégateur (Sendcloud opt-in, mature en 1.0.0) ─────
        // 'methods' => […, 'sendcloud'],
        // 'sendcloud' => ['cache_ttl' => 3600, 'markup_cents' => 0, 'carriers' => null],

        'free_shipping_threshold_cents' => 6000,
        'rates_source' => 'config', // 'database' si barèmes CSV importés
        'http_timeout' => 10,

        'flat_rate'    => ['price_cents' => 590, 'label' => 'Livraison standard', 'countries' => ['FR'], 'days' => 4],
        'store_pickup' => ['label' => "Retrait en boutique"],
        'sendcloud'    => ['cache_ttl' => 3600, 'markup_cents' => 0, 'carriers' => null],
        'rates'        => [],
    ],
];
