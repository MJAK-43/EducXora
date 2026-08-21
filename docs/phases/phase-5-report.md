# Rapport Phase 5 — Suivi des présences

Date de réalisation : 2026-08-21.

## État initial

- HEAD : `d2d3d75c61a3f0df030092b6e8d494ccaed87104` (`feat: implement groups and scheduling`).
- Tag local : `v0.4.0` — `Phase 4 - Groups and scheduling validated`.
- Branche : `main`, en avance de deux commits sur `origin/main`.
- Working tree initial : propre.
- Trois stashes de sécurité présents et conservés.

## Diagnostic et architecture réutilisée

La Phase 5 réutilise sans duplication `CourseSession`, `Group`, `GroupLearnerAssignment`, `Learner`, `OrganizationMembership`, `TenantContext`, le scope fail-closed, `MembershipAuthorizer`, les policies et `AuditLogger`. Les contrôleurs restent des adaptateurs HTTP ; les transactions et invariants sont portés par les Actions du domaine Attendance. Les décisions détaillées figurent dans ADR-016.

## Fonctionnalités livrées

- feuille unique par séance existante, roster historique calculé par le serveur ;
- pointage apprenant `present`, `absent`, `excused` et action rapide tous présents ;
- présence enseignant séparée et séances sans feuille identifiables comme non pointées ;
- brouillon modifiable puis validation atomique définitive avec auteur/date ;
- verrouillage backend après validation ;
- corrections apprenant et enseignant motivées, structurées, append-only et auditées ;
- liste paginée par période/statut, consultation de feuille et historique de corrections ;
- historique apprenant avec période, pagination, corrections et taux ;
- historique des séances d’un enseignant sur une période, sans donnée de rémunération ;
- indicateurs simples sur la fiche Groupe.

## Architecture livrée

- migration `2026_08_21_000400_create_attendance_tables.php` ;
- enums `AttendanceStatus`, `AttendanceSheetStatus` ;
- modèles `AttendanceSheet`, `LearnerAttendance`, `TeacherAttendance`, `AttendanceCorrection` ;
- Actions de démarrage, brouillon, présence enseignant, validation et corrections ;
- Queries de liste, historique apprenant/enseignant et résumé groupe ;
- Form Requests dédiés, deux contrôleurs fins, `AttendanceSheetPolicy` et cinq permissions ;
- onze routes tenant-scoped ;
- pages Vue `Index`, `Take`, `Show`, `LearnerHistory`, `TeacherHistory`, navigation et intégrations Groupe/Apprenant.

## Base de données et sécurité

Les quatre tables sont tenant-scoped, utilisent des UUIDv7 publics et des FK composites avec `organization_id`. Les contraintes PostgreSQL imposent les statuts, la cohérence des timestamps, un sujet de correction unique et un motif non vide. Les unicités interdisent plusieurs feuilles par séance et plusieurs relevés du même sujet. Les transactions et verrous de lignes protègent démarrage, validation et correction.

Les Form Requests interdisent les identifiants de tenant, de séance et d’enseignant injectés. Le roster provient exclusivement du serveur. Les UUID sont résolus sous `TenantContext`; les policies combinent permission, tenant et relation à l’enseignant. Les tests couvrent IDOR, tenant A/B, ressource hors roster, rôle enseignant limité et rôle Accountant refusé.

## Règle de taux

Le taux est `présent / total des relevés validés éligibles`. `absent` et `excused` restent dans le dénominateur et ne sont pas dans le numérateur. Les séances annulées sont exclues. Le taux est calculé sur une période et n’est pas persisté.

## Validation automatisée

- Laravel complet : 59 tests / 431 assertions — PASS ;
- sous-suite Attendance : 8 tests / 125 assertions — PASS ;
- Vitest complet : 6 fichiers / 28 tests — PASS ;
- sous-suite Attendance : 1 fichier / 4 tests — PASS ;
- Composer validate strict — PASS ;
- Pint — PASS ;
- Larastan — PASS ;
- ESLint — PASS ;
- Prettier — PASS ;
- vue-tsc — PASS ;
- Vite build — PASS ;
- Composer audit — PASS, aucune vulnérabilité signalée ;
- npm audit — PASS, aucune vulnérabilité signalée ;
- `migrate:fresh`, `migrate:rollback`, `migrate` — PASS sur `eduxora_testing` explicitement vérifiée ;
- PostgreSQL et Redis — PASS via `eduxora:foundation-check` ;
- services Docker — PASS, services applicatifs disponibles et services avec healthcheck sains.
- `git diff --check` — PASS ; scan ciblé de signatures de secrets — aucun résultat.

## Responsive

La structure CSS prévoit cartes, grilles adaptatives, listes empilées et contrôles pleine largeur sur mobile. Faute de runtime navigateur réel disponible, les validations visuelles 375, 390, 768, 1024, 1280, 1440 px et Safari iOS 13 restent **NOT TESTED**. Les contrôles statiques et le build ne remplacent pas cette validation.

## Limites assumées

- aucun mode offline, Service Worker, IndexedDB ou endpoint de synchronisation ;
- aucun export PDF/XLSX ni dashboard analytique global ;
- aucun calcul de salaire, tarif ou rémunération enseignant ;
- validation visuelle réelle et Safari iOS 13 non testés ;
- aucun travail de Phase 6.

## Git

Aucun commit, tag, push, merge ou rebase n’est réalisé par cette phase. Les trois stashes de sécurité restent présents. Le checkpoint `v0.5.0` n’est pas créé.

État final observé : HEAD inchangé sur `d2d3d75c61a3f0df030092b6e8d494ccaed87104`, 17 fichiers suivis modifiés, 36 fichiers non suivis, aucun fichier staged. Aucun `.env`, secret, `vendor`, `node_modules`, build, cache, log ou fichier IDE n’apparaît dans le statut.
