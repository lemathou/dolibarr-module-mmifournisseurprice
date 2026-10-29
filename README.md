# MMIFournisseurPrice — Prix fournisseurs : tarifs, historiques et comparaison.

> Module Dolibarr **MMIFournisseurPrice** (version 1.0), branche `20.0`.

## Fonctionnalités

### Fonctionnalités principales

- Tarifs fournisseurs, historiques et comparaison des prix.
- Configuration et menus dédiés.

### Écrans et menus

- **Prix de revient amélioré** — `/mmifournisseurprice/mmifournisseurpriceindex.php` (menu mmifournisseurprice, droit `1`)

### Pages

- `admin/about.php`
- `admin/setup.php`
- `mmifournisseurpriceindex.php`

### Configuration

| Constante | Type | Rôle |
|---|---|---|
| `MMIFOURNISSEURPRICE_AUTOCALCULATE_INFO` | separator | Gestion automatique de prix de revient |
| `MMIFOURNISSEURPRICE_AUTOCALCULATE` | yesno | Calcul automatique de prix de revient (lorsqu'on modifie le prix du fournisseur) |
| `MMIFOURNISSEURPRICE_AUTOCALCULATE_ORDERS` | yesno | Calcul automatique des frais d'acheminement fournisseur basé sur l'historique de commande, utilisé dans le calcul dans le calcul du prix de reviens lorsqu'un prix manuel n'est pas renseigné |
| `MMIFOURNISSEURPRICE_FK_PRODUCT_SHIPPING` | int | Produit servant au Transport |
| `MMIFOURNISSEURPRICE_DELAI` | int | Délai maxi commandes fournisseur |
| `MMIFOURNISSEURPRICE_VALIDITY_GRILLE` | separator | Gestion des grilles de produits fournisseur |
| `MMIFOURNISSEURPRICE_VALIDITY_DATE` | yesno | Gestion de la date de validité de la grille de produits fournisseur (prix d'achat) |
| `MMIFOURNISSEURPRICE_VALIDITY_DATE_EMAIL_ALERT_FROM` | string | Expéditeur de l'email d'alerte de date de validité de grille dépassée |
| `MMIFOURNISSEURPRICE_VALIDITY_DATE_EMAIL_ALERT_TO` | string | Destinataire de l'email d'alerte de date de validité de grille dépassée |
| `MMIFOURNISSEURPRICE_FRANCO_INFO` | separator | Gestion du franco de commande fournisseur |
| `MMIFOURNISSEURPRICE_FRANCO` | yesno | Affichage de l'option de franco de commande fournisseur |

### Hooks

- Contextes : `pricesuppliercard`, `productservicelist`
- Callbacks : `addMoreMassActions`, `doMassActions`, `doPreMassActions`, `ObjectExtraFields`, `h`

### Triggers

- `core/triggers/interface_99_modMMIFournisseurPrice_MMIFournisseurPriceTriggers.class.php`

### Classes et composants

- `class/actions_mmifournisseurprice.class.php`
- `class/mmifournisseurpriceutils.class.php`
- `lib/mmifournisseurprice.lib.php`

## Prérequis

- Dolibarr >= 11.0 (branche `20.0` : Dolibarr 20.0).
- Modules MMI : `mmicommon`, `mmishipping`, `mmiproduct`. **`mmicommon` doit être activé en premier.**
- Modules cœur : `product`, `fournisseur`, `reception`.

## Installation

1. Copier le module dans `htdocs/custom/mmifournisseurprice` :

   ```sh
   cd htdocs/custom
   git clone <dépôt> mmifournisseurprice
   ```

2. Dans Dolibarr : **Configuration → Modules**, activer d'abord `mmicommon`, puis ce module.
3. Vérifier les permissions dans la fiche des utilisateurs concernés.

## Licence

GPLv3 ou ultérieure — voir `COPYING`. Documentation sous GFDL.

## Changelog

Voir [CHANGELOG.md](CHANGELOG.md) (évolutions datées).
