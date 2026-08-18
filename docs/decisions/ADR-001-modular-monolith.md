# ADR-001 — Monolithe modulaire Laravel

- Statut : Accepted
- Date : 2026-08-18

## Contexte

Le périmètre comporte de nombreux domaines liés, une équipe/capacité non définie et un objectif pilote court. Des microservices multiplieraient déploiements, transactions distribuées et observabilité avant preuve de besoin. Un CRUD monolithique non structuré créerait un couplage tout aussi risqué.

## Décision

Construire un déploiement Laravel 13 unique, organisé en domaines métier explicites. Les contrôleurs restent adaptateurs HTTP ; Actions/Services portent les cas d'usage, Queries les lectures, Events/Jobs les effets. Les domaines exposent des contrats et ne lisent pas directement les tables internes d'un autre domaine hors relations approuvées.

## Conséquences

Transactions locales simples, livraison et tests plus rapides, refactoring possible. Il faut faire respecter les frontières par conventions et tests d'architecture. Un domaine pourra être extrait seulement si mesures de charge, cadence d'équipe ou besoin d'isolation le justifient.

## Alternatives écartées

Microservices dès V1 (coût disproportionné) ; architecture Laravel plate par types techniques (faibles frontières métier) ; CQRS/event sourcing généralisé (complexité sans exigence).
