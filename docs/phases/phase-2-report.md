# EduXora — Rapport de validation Phase 2

- Date : 2026-08-18
- Périmètre : authentification, organisations multi-tenant, adhésions, RBAC, invitations et audit minimal
- Base de départ : Phase 1 validée, `main` à `ba5b4c3`, tag `v0.1.0`
- Historique Git : aucun commit, push ou tag créé pendant cette phase

## Verdict

**PHASE 2 VALIDÉE localement.** Tous les critères bloquants automatisables dans l’environnement disponible sont passants. La vérification responsive par navigateur et la CI GitHub distante sont explicitement `NOT TESTED`, ce que le cahier de phase autorise lorsque ces services ne sont pas disponibles.

## Livrables

### Identité et authentification

- identité globale `User` avec UUIDv7 public, prénom, nom, e-mail/téléphone uniques, état et dernière connexion ;
- inscription transactionnelle créant utilisateur, organisation, adhésion et rôle Organization Admin ;
- connexion/logout, rotation de session, limitation à 5 tentatives/minute, refus générique des comptes inactifs ;
- vérification e-mail et renvoi limité ;
- mot de passe oublié/reset via le broker Laravel avec réponse non énumérante ;
- politique forte de 12 caractères avec casse, chiffres et symboles ;
- expiration d’inactivité Redis à 30 minutes, durée absolue de 24 heures et invalidation des autres sessions après changement de mot de passe via `auth.session` ;
- confirmation de mot de passe pour changements de rôles, réglages d’organisation et actions plateforme sensibles.

### Multi-tenant

- `organizations` comme frontière tenant et `organization_user` comme adhésion explicite ;
- sélection/changement d’organisation uniquement parmi les adhésions actives ;
- `TenantContext` scoped, scope Eloquent fail-closed et attribution serveur de `organization_id` ;
- middlewares indépendants pour utilisateur, adhésion et organisation actifs ;
- FK composites sur `membership_role` empêchant une attribution de rôle cross-tenant ;
- routes Super Admin globales séparées des routes tenantées ;
- console plateforme : création, édition, suspension et réactivation d’organisations.

### RBAC

- `spatie/laravel-permission` 8.3 compatible Laravel 13/PHP 8.4 ;
- catalogue de permissions atomiques `organization.*`, `users.*`, `roles.*`, `permissions.*`, `audit.view` ;
- rôles système initiaux : Super Admin, Organization Admin, Manager, Teacher/Trainer, Accountant et Staff ;
- rôles tenantés portés par l’adhésion, Super Admin porté uniquement par l’identité globale ;
- Gates, policies et `MembershipAuthorizer` combinant permission, tenant et états ;
- rôles système protégés ; création, modification, suppression et attribution de rôles personnalisés disponibles selon permissions.

### Invitations et audit

- invitation liée à organisation/e-mail/rôle/invitant, expiration 72 heures ;
- jeton aléatoire envoyé une fois, uniquement son hash SHA-256 est stocké ;
- acceptation transactionnelle, e-mail identique obligatoire, usage unique et refus des jetons expirés ;
- audit UUIDv7 structuré et append-only pour connexions, échecs/blocages, vérification/reset, organisations, adhésions, rôles et invitations ;
- métadonnées récursivement expurgées des mots de passe et jetons.

### Interface Inertia/Vue

- pages connexion, inscription, oubli/reset, vérification, confirmation et acceptation d’invitation ;
- sélection d’organisation, dashboard, réglages, utilisateurs/invitations et rôles/permissions ;
- console Super Admin et changement de tenant dans la topbar ;
- navigation Phase 2 uniquement, états permissions/disabled et formulaires accessibles avec labels/erreurs.

## Schéma livré

Migrations ajoutées :

1. `organizations` ;
2. enrichissement `users` ;
3. `organization_user` ;
4. `permissions`, `roles`, tables Spatie et `membership_role` ;
5. `user_invitations` ;
6. `audit_logs`.

Le cycle PostgreSQL dédié `eduxora_testing` a passé `migrate:fresh`, rollback complet puis `migrate`.

## Validation exécutée

| Contrôle | Résultat |
|---|---|
| Migrations PostgreSQL fresh / rollback / migrate | PASS |
| PHPUnit backend | PASS — 21 tests, 77 assertions |
| Isolation A/B et cross-tenant | PASS |
| RBAC Manager/Admin et ressource étrangère | PASS |
| Invitations expirées/non rejouables | PASS |
| Audit append-only/expurgation | PASS |
| Pint | PASS — 83 fichiers |
| Larastan/PHPStan | PASS — 0 erreur |
| ESLint | PASS |
| Prettier | PASS |
| Vue TypeScript | PASS |
| Vitest frontend | PASS — 8 tests |
| Build Vite production | PASS |
| Composer validate | PASS |
| Composer audit | PASS — aucune vulnérabilité connue |
| NPM audit production | PASS — 0 vulnérabilité |
| Responsive navigateur réel | NOT TESTED — Node REPL navigateur absent |
| GitHub Actions distante | NOT TESTED — aucun push demandé/autorisé |

## Tests de sécurité significatifs

- une requête tenantée sans contexte lève une erreur au lieu de retourner toutes les lignes ;
- une invitation créée dans l’organisation A est invisible dans B ;
- un utilisateur A ne peut pas sélectionner B ;
- un Manager ne peut pas créer un rôle, un Organization Admin le peut ;
- un rôle B injecté dans une route A est refusé ;
- une organisation suspendue retourne 403 ;
- le token d’invitation en clair n’existe pas en base, expire et ne peut pas être rejoué ;
- un audit ne peut pas être supprimé et n’enregistre pas les mots de passe.

## Décisions et limites assumées

- ADR-010 remplace le terme `center_id` de l’ADR-002 par `organization_id`, conforme au contrat Phase 2.
- ADR-011 formalise le RBAC d’adhésion et la séparation Super Admin.
- ADR-012 définit la sécurité des invitations.
- ADR-013 complète l’audit de l’ADR-008.
- PostgreSQL RLS est différée : elle nécessite d’abord une preuve fiable du contexte sur connexions persistantes et workers. Le scope fail-closed, les policies et FK composites sont actifs dès maintenant.
- MFA est préparée mais non implémentée, conformément à l’instruction de phase. Elle reste obligatoire avant mise en production publique des comptes Super Admin.
- La protection append-only est applicative ; un contrôle DB/WORM et la politique de conservation restent du hardening pré-production.

## Hors périmètre respecté

Aucun module apprenants, groupes, planning, présences, pédagogie, finance, paiements, communications ou reporting n’a été implémenté. Aucune API métier Phase 3+ n’a été anticipée.
