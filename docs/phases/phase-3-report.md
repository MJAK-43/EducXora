# Rapport de Phase 3 — Gestion des apprenants

Date de validation technique : 2026-08-21

## Résultat

La gestion administrative de base des apprenants est implémentée de bout en bout dans le périmètre explicite de Phase 3. Elle réutilise le socle Phase 2 sans introduire de groupe ni de fonctionnalité des phases suivantes.

## Livré

- domaine `app/Domain/Learner` : enums langue/niveau/statut, modèle, requête paginée, stockage photo, présentation et quatre Actions transactionnelles ;
- migration `learners` avec UUIDv7 exposé, `organization_id` obligatoire, contraintes CECRL/statut/langue, index tenant et traçabilité d’archivage ;
- relation `Organization::learners()` sans relation future fictive ;
- permissions atomiques `learners.view/create/update/archive/restore/export`, policy explicite et profils provisionnés ;
- validation par Form Requests, rejet de `organization_id`/`center_id` client, téléphone camerounais normalisé et photo privée contrôlée ;
- routes liste/création/fiche/édition/archivage/restauration/photo/export dans le groupe middleware tenant ;
- recherche nom/téléphone/e-mail, filtres statut/niveau/langue, pagination à 20, vue actifs/archivés ;
- écrans Inertia Vue 3/TypeScript `Index`, `Create`, `Show`, `Edit`, formulaire partagé, états vides, erreurs accessibles et transformation table→cartes sur mobile ;
- audit append-only des créations, modifications, archivages et restaurations ;
- export CSV UTF-8 compatible tableur, respectant les filtres, la permission et le tenant courant.

## Matrice d’autorisation retenue

| Profil existant        | Accès apprenants                          |
| ---------------------- | ----------------------------------------- |
| Organization Admin     | complet                                   |
| Manager                | complet                                   |
| Staff                  | voir, créer, modifier, archiver, exporter |
| Accountant             | voir, créer, modifier, archiver, exporter |
| Teacher/Trainer        | aucun accès administratif global          |
| Super Admin plateforme | bypass explicite existant                 |

`Staff` et `Accountant` matérialisent les fonctions administratives/secrétariat disponibles dans le référentiel de rôles actuel. La restauration reste réservée aux profils de direction/administration.

## Vérifications exécutées

- Composer strict : valide ;
- Pint : 107 fichiers contrôlés, succès ;
- Larastan : 69 fichiers analysés, aucune erreur ;
- Laravel : 32 tests réussis, 156 assertions ;
- Vitest : 4 fichiers et 11 tests réussis ;
- TypeScript, ESLint, Prettier et build Vite : succès ;
- migration sur `eduxora_testing` : `migrate:fresh`, rollback de la migration Learner puis `migrate`, succès.

Les tests Phase 3 couvrent notamment la création, la normalisation du téléphone, la recherche, la pagination, les filtres, les rôles, l’audit, l’archivage/restauration, la photo privée et l’interdiction pour l’organisation A de voir, modifier, archiver, restaurer, télécharger ou exporter les données de l’organisation B.

## Exclusions respectées

Groupes, inscriptions pédagogiques, planning, présences, évaluations, paiements, finance, communication et reporting n’ont pas été implémentés. Leur présence dans la fiche est uniquement un repère visuel désactivé, sans données ni logique métier.

## Dette explicite

- XLSX/PDF natifs : non ajoutés, car aucune dépendance dédiée n’existait et l’ajout de deux moteurs document/export aurait élargi inutilement le socle ; le CSV tableur est fonctionnel.
- Consentement/tuteur pour la photo d’un mineur et politique de rétention/anonymisation : règles métier toujours ouvertes dans `docs/requirements.md`.
- Validation navigateur réelle aux six largeurs cibles : à automatiser dans la future suite E2E ; le responsive est couvert structurellement et le build/typecheck sont validés.

## État Git

Aucun commit, tag ou push n’a été créé. Les changements Phase 2 préexistants ont été conservés.
