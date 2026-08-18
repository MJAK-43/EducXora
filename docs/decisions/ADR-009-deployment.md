# ADR-009 — Conteneurs et promotion d'image immuable

- Statut : Provisional
- Date : 2026-08-18

## Contexte

Les environnements doivent être séparés et reproductibles. L'hébergeur, le registre, le stockage objet, le coffre de secrets et les services managés ne sont pas encore choisis.

## Décision

Docker Compose sert au développement. Recette et production promeuvent le même digest d'image non-root derrière Nginx, avec web/workers/scheduler séparés et PostgreSQL/Redis/stockage persistants. Les migrations suivent expand/contract et un job unique. CI utilise OIDC et secrets d'environnement dès que la plateforme est choisie.

## Conséquences

Parité et rollback applicatif améliorés. Il faut choisir une cible d'hébergement, établir coûts/SLO, gérer backups et superviser workers. La décision reste provisoire jusqu'au cadrage infrastructure Phase 1/14.

## Alternatives écartées à ce stade

Déploiement manuel de fichiers ; construction différente par environnement ; dépendances installées sur serveur de production.
