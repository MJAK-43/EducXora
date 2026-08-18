# ADR-007 — Effets externes asynchrones et adapters

- Statut : Accepted
- Date : 2026-08-18

## Contexte

Mobile Money, SMS, WhatsApp, email, PDF et exports sont lents ou faillibles. Les exécuter dans la requête HTTP dégrade la 3G et encourage les retries utilisateurs.

## Décision

Déclencher après commit des jobs idempotents via Redis queues, séparés par criticité. Chaque fournisseur implémente un contrat et un fake. Retries, backoff, timeout, dead jobs, correlation et observabilité sont obligatoires. Les événements métier ne contiennent que les références nécessaires et respectent le tenant.

## Conséquences

Requêtes rapides et pannes isolées, mais cohérence éventuelle visible dans l'UI. Il faut états `QUEUED/SENT/DELIVERED/FAILED`, workers supervisés et outils de rejeu sécurisé.

## Alternatives écartées

Appels synchrones en contrôleur ; couplage direct SDK ; retry infini ; queue unique sans priorité.
