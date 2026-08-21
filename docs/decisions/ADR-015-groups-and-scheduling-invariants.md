# ADR-015 — Groupes, affectations historiques et conflits de planning

- Statut : Accepted
- Date : 2026-08-21
- Complète : ADR-001, ADR-008, ADR-010, ADR-011 et ADR-014

## Contexte

La demande explicite de Phase 4 regroupe la gestion des groupes/classes et le planning hebdomadaire. Le backlog initial séparait ces capacités entre les Phases 4 et 5 ; ce cadrage plus récent fait autorité. Les documents existants imposent déjà l’absence de chevauchement d’un groupe, d’un enseignant ou d’une salle et définissent des intervalles semi-ouverts `[début, fin)`.

Une affectation directe `group_id` sur l’apprenant perdrait l’historique. Une référence d’enseignant par utilisateur global ne prouverait pas son appartenance active à l’organisation. Une détection de conflit uniquement en PHP resterait vulnérable aux écritures concurrentes.

## Décision

- Les groupes et affectations vivent dans `app/Domain/Learning`; les séances et conflits vivent dans `app/Domain/Scheduling`.
- Un groupe porte un UUIDv7, `organization_id`, nom, langue, niveau, capacité, statut et `teacher_membership_id`. La clé étrangère composite lie l’enseignant à une adhésion du même tenant.
- Seules les adhésions actives possédant le rôle système `Teacher/Trainer` sont éligibles. Le client manipule leur UUID, jamais l’identifiant du tenant.
- `group_learner_assignments` conserve attribution et retrait, acteurs et timestamps. Un index partiel garantit au plus une affectation active par apprenant dans l’organisation.
- Une affectation exige un groupe et un apprenant actifs, un niveau compatible, une place disponible et aucune affectation active existante. La langue est également cohérente par les contraintes de catalogue actuelles, limitées à l’allemand.
- L’édition d’un groupe verrouille sa ligne avant de contrôler l’effectif. Elle refuse une capacité inférieure à l’effectif actif ainsi que toute langue ou tout niveau incompatible avec les apprenants déjà affectés.
- L’archivage d’un groupe conserve ses membres et fige les affectations. Il est refusé tant qu’une séance planifiée future existe. La restauration exige que l’enseignant responsable soit toujours éligible.
- Une séance porte groupe, adhésion enseignant, salle textuelle facultative, début/fin UTC et statut `scheduled` ou `cancelled`. Les saisies sont interprétées dans le fuseau de l’organisation.
- PostgreSQL `btree_gist` et trois contraintes d’exclusion empêchent les chevauchements actifs par groupe, enseignant et salle normalisée sans tenir compte de la casse. Une validation applicative fournit des messages précis ; la contrainte DB protège la concurrence.
- L’annulation est une transition auditée, jamais une suppression. Les événements d’audit stables sont `group.*` et `lesson.*`. Aucun événement de notification, SMS, WhatsApp ou e-mail n’est émis dans cette phase.

## Conséquences

L’historique est exploitable par les futures phases de présence et de reporting sans mutation rétroactive. Les liens enseignant/groupe/séance ne peuvent pas traverser un tenant, y compris en cas d’erreur applicative. Deux séances adjacentes sont autorisées, mais toute intersection réelle est rejetée jusque dans la base.

L’extension `btree_gist` doit pouvoir être activée par le rôle de migration PostgreSQL. La salle reste une valeur textuelle contrôlée dans cette phase : un catalogue `rooms` et ses archives ne sont pas anticipés sans besoin explicite. La récurrence et les notifications restent hors périmètre.

## Alternatives écartées

`group_id` sur `learners` ; enseignant référencé par `user_id` ; suppression physique des affectations ou séances ; contrôles de chevauchement uniquement en application ; créneaux à bornes fermées ; création prématurée d’un domaine Communication ou d’un catalogue de salles.

## Critères de révision

Réviser si plusieurs affectations actives par apprenant deviennent nécessaires, si un catalogue de salles est demandé, si les séances récurrentes sont introduites ou si le déploiement PostgreSQL n’autorise pas la gestion contrôlée de `btree_gist`.
