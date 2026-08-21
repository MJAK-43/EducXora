# Rapport de Phase 4 — Groupes, classes et planning

Date de validation technique : 2026-08-21

## Résultat

La gestion des groupes/classes et le planning hebdomadaire sont implémentés de bout en bout dans le périmètre explicite de Phase 4. Le découpage historique plaçait le planning en Phase 5 ; la demande plus récente a rebaseliné ce lot et fait autorité. Aucun module futur ni notification n’a été introduit.

## Livré

- domaine `app/Domain/Learning` : groupe, statut, affectation historisée, Actions transactionnelles, Queries et presenters ;
- domaine `app/Domain/Scheduling` : séance, statut, conversion fuseau/UTC, détection de conflits, Actions, Query hebdomadaire et presenter ;
- migrations `groups`, `group_learner_assignments` et `course_sessions`, UUIDv7, clés composites tenant, checks SQL, index partiels et contraintes d’exclusion PostgreSQL ;
- UUID ajouté aux adhésions afin de sélectionner un enseignant sans exposer une clé interne ;
- permissions atomiques `group.*` et `schedule.*`, policies ressource et filtrage propre à l’enseignant ;
- CRUD groupe, recherche, filtres, pagination, archivage/restauration, capacité et enseignant responsable ;
- ajout/retrait d’apprenants actifs compatibles, sans duplication, avec capacité verrouillée et historique conservé ;
- création, édition et annulation de séances, vue semaine, navigation précédente/actuelle/suivante et filtres groupe/enseignant ;
- interfaces Vue 3/TypeScript accessibles, tables transformées en cartes sur mobile et planning en cartes quotidiennes puis grille hebdomadaire ;
- audits `group.created`, `group.updated`, `group.archived`, `group.restored`, `group.learner_attached`, `group.learner_detached`, `group.teacher_changed`, `lesson.created`, `lesson.updated` et `lesson.cancelled`.

## Matrice d’autorisation retenue

| Profil existant        | Groupes                                         | Planning                              |
| ---------------------- | ----------------------------------------------- | ------------------------------------- |
| Organization Admin     | complet                                         | complet                               |
| Manager                | complet                                         | complet                               |
| Staff                  | complet, dont membres et restauration           | créer, modifier, annuler              |
| Accountant             | aucun accès Phase 4                             | aucun accès Phase 4                   |
| Teacher/Trainer        | lecture de ses groupes uniquement               | lecture de ses séances uniquement     |
| Super Admin plateforme | bypass explicite existant, hors parcours tenant | bypass explicite existant             |

`Staff` matérialise le secrétariat dans le catalogue de rôles actuel. Les enseignants n’obtiennent aucune mutation administrative par simple visibilité UI.

## Invariants validés

- `organization_id` provient exclusivement du `TenantContext` et les UUID sont résolus après le middleware tenant ;
- les FK composites empêchent un groupe ou une séance de référencer une adhésion, un apprenant ou un groupe d’une autre organisation ;
- un apprenant possède au plus une affectation active et son historique de retraits reste intact ;
- les groupes archivés ne reçoivent ni ajout ni retrait et une séance future doit être annulée avant archivage ;
- la capacité est contrôlée sous verrou et ne peut pas devenir inférieure à l’effectif ;
- une édition ne peut pas rendre la langue ou le niveau du groupe incompatible avec ses apprenants actifs ;
- les intervalles sont `[début, fin)` : les séances adjacentes passent, les chevauchements groupe/enseignant/salle échouent ;
- l’annulation libère le créneau sans détruire la séance.

## Vérifications exécutées

- migration PostgreSQL : `migrate:fresh`, `migrate:rollback`, puis `migrate`, succès ;
- Laravel : 51 tests réussis, 306 assertions, dont 19 tests Phase 4 ;
- Vitest : 5 fichiers, 24 tests réussis, dont 13 tests Phase 4 ;
- Larastan, Pint, Composer strict, ESLint, Prettier, TypeScript strict et build Vite : succès.

Les tests Phase 4 couvrent notamment CRUD, recherche/filtres/pagination, rôles, enseignants éligibles, audit, capacité réelle sous la borne métier, compatibilité lors de l’affectation et de l’édition, apprenant archivé, duplication, historique, tenant A/B, fuseau horaire, semaine, annulation, créneaux adjacents et collisions groupe/enseignant/salle.

## Exclusions respectées

Présences, évaluations, finance, paiements, communication, SMS, WhatsApp, e-mail, reporting, mode offline, récurrence et catalogue de salles n’ont pas été implémentés. Aucun événement de notification n’est émis.

## Dette explicite

- validation visuelle réelle aux largeurs 375, 390, 768, 1024, 1280 et 1440 px : **NOT TESTED** dans cette session ; structure responsive, lint, tests composants et compilation sont validés ;
- navigateur Safari iOS 13 réel : **NOT TESTED** ;
- CI GitHub distante : **NOT TESTED**, aucun commit ni push ne faisant partie de cette livraison ;
- création de `btree_gist` : vérifier le privilège du rôle de migration sur chaque environnement avant déploiement.

## État Git

Aucun commit, tag, push, merge ni rebase n’a été créé. La Phase 4 reste intégralement non commitée au-dessus du checkpoint local `v0.3.0`.
