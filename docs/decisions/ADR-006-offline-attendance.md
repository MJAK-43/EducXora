# ADR-006 — Synchronisation offline par commandes

- Statut : Accepted
- Date : 2026-08-18

## Contexte

Le pointage doit fonctionner avec connectivité partielle. Une copie locale complète ou une fusion last-write-wins pourrait exposer des données et écraser une feuille validée ailleurs.

## Décision

Mettre en cache uniquement shell, séances nécessaires et listes minimales. Enregistrer dans IndexedDB des commandes avec UUID, version de base et payload. Le serveur vérifie tenant/droits/état, déduplique et renvoie résultat ou conflit explicite. Le serveur reste autoritaire ; « synchronisé » nécessite un acquittement.

## Conséquences

Résilience sans masquer les conflits. Une UI de résolution, une compatibilité de schéma IndexedDB et des tests réseau/appareil sont nécessaires. Les paiements et autres domaines restent online-only.

## Alternatives écartées

Cache complet, localStorage, dernier écrivain gagne, base locale bidirectionnelle générale et validation finale purement locale.
