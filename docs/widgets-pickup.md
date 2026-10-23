# Widgets point relais (Colissimo & Mondial Relay)

> Guide **0.5.0** — le package valide et stocke `pickup_point_*` ; le **choix UI** reste côté boutique via le widget du transporteur.  
> Voir aussi [`colissimo-mondial-relay.md`](colissimo-mondial-relay.md) et le partial `stubs/views/shipping/options.php`.

---

## Rôle de chacun

| Couche | Responsabilité |
| --- | --- |
| Widget transporteur (JS) | Carte / liste des points, sélection client |
| Formulaire checkout | Remplir `pickup_point_id` + `pickup_point_label` |
| `ShippingService::select()` | Exiger un id non vide si `requiresPickupPoint` ; recalculer le prix |
| `order_shipments` | Snapshot id + libellé |

Le serveur **ne vérifie pas** que le point existe chez le transporteur (pas d’API point dans 0.5.0).

---

## Champs attendus (POST)

| Champ | Obligatoire si relais | Exemple |
| --- | --- | --- |
| `shipping_method` | oui | `mondial_relay` / `colissimo_pickup` |
| `pickup_point_id` | oui | `012345` / code Colissimo |
| `pickup_point_label` | recommandé | `Relais Tabac — 10 rue…` |
| CSRF | oui (formulaire hôte) | token Astral / session |

Les radios du partial exposent `data-requires-pickup="1"` quand un point est requis.

---

## Pattern JS minimal

```js
const widget = document.getElementById('pickup-widget');
const idField = document.getElementById('pickup_point_id');
const labelField = document.getElementById('pickup_point_label');

function syncPickupVisibility() {
  const selected = document.querySelector('input[name="shipping_method"]:checked');
  const needs = selected?.dataset.requiresPickup === '1';
  widget.classList.toggle('hidden', !needs);
  if (!needs) {
    idField.value = '';
    labelField.value = '';
  }
}

document.getElementById('shipping-options')?.addEventListener('change', syncPickupVisibility);
syncPickupVisibility();

// Dans le callback « point sélectionné » du widget officiel :
function onPickupSelected(point) {
  idField.value = String(point.id);       // adapter au nom du champ widget
  labelField.value = String(point.label); // ou name + address
}
```

Adapte `point.id` / `point.label` à la doc du widget (souvent `ID`, `Nom`, `Adresse`…).

---

## Mondial Relay

1. Compte enseigne + accès widget / ParcelShop (doc Mondial Relay).
2. Intégrer le script / iframe fourni (paramètres : code enseigne, pays, CP…).
3. Sur sélection : mapper l’identifiant ParcelShop → `pickup_point_id`, libellé humain → `pickup_point_label`.
4. N’afficher le widget que si `shipping_method === mondial_relay` (ou radio `data-requires-pickup="1"`).
5. Respecter les plafonds poids / dimensions (Point Relais **30 kg**, Lockers / IT / PL **25 kg**, développé 150 cm…) — détail dans [`colissimo-mondial-relay.md`](colissimo-mondial-relay.md). Le barème CSV doit s’arrêter à ces max.

**Liens utiles** (vérifier les URLs à jour chez le transporteur) :

- Documentation technique / widgets : portail partenaires Mondial Relay  
- [Taille / poids max colis](https://www.mondialrelay.fr/faq-pro/envoyer-un-colis/quelle-est-la-taille-maximale-pour-envoyer-un-colis/)  
- Recherche ParcelShop : API ou widget selon contrat

---

## Colissimo (point de retrait)

1. Offre **point de retrait** = méthode `colissimo_pickup` + barème.
2. Widget / carte officielle Colissimo (espace client pro / doc « module points de retrait »).
3. Même mapping : id point → `pickup_point_id`, libellé → `pickup_point_label`.
4. Afficher le widget seulement si la méthode sélectionnée exige un point.

---

## Checklist intégration boutique

- [ ] Partial `options.php` (ou équivalent) avec champs cachés + `data-requires-pickup`
- [ ] CSRF sur le formulaire de validation shipping
- [ ] Widget chargé uniquement si méthode relais cochée
- [ ] Callback widget → champs cachés avant submit
- [ ] Côté serveur : `ShippingService::select()` (ne pas faire confiance au prix POST)
- [ ] Afficher le libellé du point sur récap commande / emails
- [ ] Barèmes importés (`shipping:rates:import`) pour les `method_code` activés
- [ ] Produits : poids (`weight_g`) renseignés pour des devis réalistes

---

## Hors scope package (volontaire)

- Bundle JS Colissimo / Mondial Relay dans le dépôt  
- Admin UI d’import CSV  
- Colonne poids produits  

→ **Astral Shop** (lot intégration shipping) + ce guide.
