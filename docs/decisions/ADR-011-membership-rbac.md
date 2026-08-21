# ADR-011 — RBAC porté par l’adhésion

- Statut : Accepted
- Date : 2026-08-18
- Remplace partiellement : ADR-003

## Contexte

Les rôles d’un même utilisateur peuvent différer selon l’organisation. Attacher tous les rôles au modèle `User` ferait fuiter des privilèges entre tenants. Le Super Admin, lui, est une responsabilité plateforme et ne doit pas être membre artificiel de toutes les organisations.

## Décision

Spatie Laravel Permission 8 fournit le catalogue global de permissions et le cache. Les rôles tenantés portent `organization_id` et sont reliés à `organization_user` par `membership_role`. Une FK composite garantit que l’adhésion et le rôle appartiennent à la même organisation. Seul le rôle global système `Super Admin` peut être attribué directement à `User`.

Les autorisations combinent : utilisateur actif, adhésion active, organisation active, permission atomique, concordance du tenant et état de la ressource. Les rôles système d’organisation sont protégés contre modification/suppression ; des rôles personnalisés peuvent être gérés avec `roles.*` et `permissions.assign`.

## Conséquences

Le changement de tenant recalcule les permissions depuis l’adhésion correcte. Les contrôleurs ne font jamais confiance à un rôle transmis par le client. L’interface masque les actions non disponibles, mais les Gates/policies restent l’autorité serveur.

MFA n’est pas implémentée dans cette phase, conformément au périmètre Phase 2 ; son ajout pour Super Admin reste bloquant avant production publique.

## Alternatives écartées

Colonne `role` sur `users`, mode teams Spatie sans adhésion explicite, et Super Admin membre de chaque tenant.

## Critères de révision

Revoir si les permissions deviennent contextuelles à une ressource métier, lors de l’ajout d’impersonation support ou avant l’activation de MFA.
