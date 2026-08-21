# ADR-013 — Audit de sécurité append-only en Phase 2

- Statut : Accepted
- Date : 2026-08-18
- Complète : ADR-008

## Contexte

Les créations d’organisation, connexions, invitations, modifications d’adhésion, changements de rôles et suspensions doivent être attribuables sans recopier de secrets ni transformer l’audit en stockage métier.

## Décision

`audit_logs` enregistre UUIDv7, tenant facultatif, acteur, action stable, type/identifiant de ressource, métadonnées minimales expurgées, IP, user-agent et date. Le modèle refuse update/delete et le service retire récursivement mot de passe et jetons. Les opérations plateforme restent identifiables par leur acteur global.

## Conséquences

Les événements de sécurité peuvent être reconstitués sans exposer les credentials. L’immutabilité applicative ne remplace pas une protection base ou un stockage WORM : celles-ci sont différées au hardening d’exploitation.

## Alternatives écartées

Logs texte non structurés, snapshots complets de requêtes, et audit mutable via CRUD générique.

## Critères de révision

Avant production : définir conservation, export sécurisé, contrôle DB anti-mutation et stratégie d’archivage inviolable.
