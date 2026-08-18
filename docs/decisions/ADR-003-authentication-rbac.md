# ADR-003 — Identité, adhésions et RBAC

- Statut : Accepted
- Date : 2026-08-18

## Contexte

Le PDF limite un utilisateur à un centre en V1, tandis que le SaaS ajoute un Super Admin transverse et peut évoluer vers plusieurs sites/centres. Les rôles ne doivent pas être de simples drapeaux sur `users`.

## Décision

Séparer identité globale `users` et `center_memberships`. Une adhésion métier active unique est imposée en V1. Rôles et permissions atomiques sont attachés à l'adhésion. Les policies combinent permission, tenant, état et relation métier. Le Super Admin utilise des permissions plateforme hors adhésion tenant. Session inactive 30 min, absolue 24 h ; MFA Super Admin obligatoire.

## Conséquences

Le modèle évolue sans fusionner tenants et identité. Les requêtes d'autorisation sont plus explicites. La personnalisation de rôles doit préserver des permissions non délégables et une séparation des tâches à préciser.

## Alternatives écartées

Colonne `role` sur `users` (non extensible) ; masque UI seul (non sécurisé) ; Super Admin membre artificiel de tous les centres (surface de fuite).
