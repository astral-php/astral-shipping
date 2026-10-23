# Sandbox / tests API — Colissimo & Mondial Relay

> **Statut Astral (0.5.x)** : le package calcule le port via **barèmes** (`shipping_rates`).  
> Les API natives transporteur (étiquette, suivi, dépôt) sont **hors scope 0.5** — doc de référence pour un spike / **1.0.0+** ou intégration shop ultérieure.  
> **MAJ :** 2026-10-23  
> Règle skill : lire la doc complète (possibilités + limites) **avant** de coder.

Aucun des deux ne propose un bac à sable **public anonyme sans inscription** pour tout le périmètre. La démarche diffère.

---

## 1. Colissimo (La Poste)

Environnement **Sandbox / Staging** aligné sur la prod. Étiquettes avec mention **« DEMO »** — ne pas coller sur de vrais colis.

| Sujet | Détail |
| --- | --- |
| Accès | **Compte Colissimo Entreprise** obligatoire — pas de compte test public anonyme |
| Identifiants | Identifiant Compte Entreprise (souvent 6 chiffres) + mot de passe → valables **test et prod** (selon doc) |
| Doc SLS | [Redoc SLS Colissimo](https://www.colissimo.fr/doc-colissimo/redoc-sls/fr) |
| Validation sans consommer un n° | Appeler **`checkGenerateLabel`** plutôt que `generateLabel` |
| Endpoints | Autour de `ws.colissimo.fr` / routes doc technique (REST/SOAP selon module) |
| Suivi seul | Espace test via [Okapi Developer La Poste](https://developer.laposte.fr/) (gratuit, indépendant) |

Réf. complémentaires : doc mise en production / Roekish (Compte Entreprise).

---

## 2. Mondial Relay

Historiquement **SOAP/WSDL** ; REST plus récent selon offres.

| Sujet | Détail |
| --- | --- |
| Accès sandbox | Identifiants **génériques de test** (communauté / doc) — immédiats |
| Enseigne | `BDTEST` (parfois 8 car. : `BDTEST` + 2 espaces — vérifier selon le client SOAP) |
| Clé privée | `PrivateK` |
| Endpoint SOAP classique | `https://mondialrelay.com` (cibler selon WSDL / doc à jour) |
| Étiquettes | Factices (PDF / Zebra) pour maquettes d’impression |

**Ne jamais** utiliser `BDTEST` / `PrivateK` en production.

---

## 3. Synthèse

| Critère | Mondial Relay | Colissimo |
| --- | --- | --- |
| Accès sandbox | Immédiat (enseigne test publique) | Sur **contrat** Entreprise |
| Identifiants test | `BDTEST` / `PrivateK` | Tes accès Compte Entreprise |
| Format API | SOAP (WSDL) + REST selon modules | REST / SOAP selon modules |
| Mention étiquette | Test / factice | **DEMO** |

---

## 4. Implications Astral

| Couche actuelle | Rôle |
| --- | --- |
| Barèmes CSV + `TableRateMethod` | Port checkout (0.5.x / shop 1.1) |
| Widgets relais | Choix point côté navigateur (`widgets-pickup.md`) |
| API SLS / WSDL | Futur : étiquettes, dépôt, éventuellement tarifs live — **spike avant 1.0.0** |

Plafonds poids / dimensions : [`colissimo-mondial-relay.md`](colissimo-mondial-relay.md).
