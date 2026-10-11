<?php

// Extrait de config/app.php — voir README pour le détail de chaque clé.

return [
    // …
    'shipping' => [
        // MVP recommandé : forfait FR + retrait (+ franco)
        'methods' => ['flat_rate', 'store_pickup'],
        // Avancé (opt-in) : ajouter 'colissimo_home', 'colissimo_pickup',
        // 'mondial_relay', 'mondial_relay_home', 'sendcloud' + barèmes / clés.

        'free_shipping_threshold_cents' => 6000,
        'rates_source' => 'config', // 'database' si barèmes CSV importés
        'http_timeout' => 10,

        'flat_rate'    => ['price_cents' => 590, 'label' => 'Livraison standard', 'countries' => ['FR'], 'days' => 4],
        'store_pickup' => ['label' => "Retrait en boutique"],
        'sendcloud'    => ['cache_ttl' => 3600, 'markup_cents' => 0, 'carriers' => null],
        'rates'        => [],
    ],
];
