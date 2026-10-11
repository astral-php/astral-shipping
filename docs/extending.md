# Ajouter un transporteur ou une API

## 1. Implémenter l'interface

```php
namespace App\Shipping;

use AstralShipping\Contract\ShippingMethodInterface;
use AstralShipping\Models\{ShippingContext, ShippingQuote};

final class ChronopostMethod implements ShippingMethodInterface
{
    public function code(): string  { return 'chronopost'; }
    public function label(): string { return 'Chronopost'; }

    public function quotes(ShippingContext $context): array
    {
        if ($context->destination->country !== 'FR') {
            return [];                      // 0 offre = non applicable
        }

        return [new ShippingQuote(
            code: 'chronopost',             // unique dans tout le calculateur
            label: 'Chronopost 13h',
            carrier: 'chronopost',
            priceCents: 1290,
            currency: $context->currency,
            estimatedDays: 1,
        )];
    }
}
```

- Retourne `[]`, **une** offre, ou **plusieurs** (agrégateur).
- Le `code` de chaque offre doit être **unique** (ex. `sendcloud:117`).
- En cas de panne (API, réseau), lance `ShippingException` : le calculateur isole la méthode et logue l'erreur.

## 2. L'enregistrer explicitement

Dans un provider de ton application, chargé **après** `ShippingServiceProvider` dans `config/dependencies.php` :

```php
// app/Providers/AppServiceProvider.php — register()
$container->make(\AstralShipping\Service\ShippingCalculator::class)
          ->register(new \App\Shipping\ChronopostMethod());
```

Rien n'est découvert automatiquement : si la méthode n'est pas enregistrée, elle n'existe pas.

## 3. Un autre agrégateur (Boxtal, ShipEngine…)

Même modèle que `SendcloudMethod` :

1. Reçois un `HttpClientInterface` dans le constructeur (testable avec `FakeHttpClient`).
2. Mets en cache l'appel tarifaire via la closure `$remember` (`fn(string $key, int $ttl, callable $producer)`), compatible avec `Core\Cache::remember`.
3. Convertis les prix en **centimes** (`(int) round($euros * 100)`).
4. Lance `ShippingException` si le statut HTTP ou le JSON est inattendu.

## 4. Tester sans réseau

```php
$http = new FakeHttpClient(200, $jsonFixture);   // tests/Support/FakeHttpClient.php
$quotes = (new MaMethodeApi($http, 'key'))->quotes($context);
```

## Note sur Sendcloud

`SendcloudMethod` utilise `GET /api/v2/shipping_methods`. Sendcloud migre ses intégrations vers l'API v3 : vérifie sa documentation avant la mise en production et, si besoin, écris une variante `SendcloudV3Method` — le reste du package n'a pas à changer.
