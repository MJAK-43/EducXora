# Modèle de données macro

## Phase 2 — identité et organisations

`users` est l’identité globale et utilise un UUIDv7 public tout en conservant une clé primaire bigint interne. `organizations` est la frontière de tenant. `organization_user` représente l’adhésion unique d’un utilisateur à une organisation avec son état et sa date d’entrée.

Le catalogue `permissions` est global. `roles.organization_id` distingue les rôles tenantés du seul rôle plateforme global `Super Admin`. `membership_role` relie un rôle à une adhésion ; ses deux clés étrangères composites imposent en base que rôle et adhésion partagent le même `organization_id`.

`user_invitations` porte l’organisation et le rôle, stocke uniquement l’empreinte du token et interdit deux invitations actives concurrentes pour le même e-mail/tenant. `audit_logs` est append-only au niveau applicatif et accepte un tenant nullable pour les événements plateforme.

Les UUID sont utilisés dans les URLs ; les identifiants bigint restent réservés aux jointures internes. Tous les futurs modèles métier tenantés doivent porter `organization_id`, utiliser `BelongsToTenant`, recevoir des FK composites lorsque la relation traverse deux tables tenantées et des tests d’isolation A/B.

Ce document décrit les agrégats et contraintes attendus, pas encore des migrations. Les noms définitifs seront validés à chaque phase.

## Présences implémentées en Phase 5

Les tables effectives sont `attendance_sheets`, `learner_attendances`, `teacher_attendances` et `attendance_corrections`. Chaque table métier porte `organization_id`; les FK composites empêchent toute référence à une séance, un groupe, une adhésion ou un apprenant d’une autre organisation.

Une feuille est unique par organisation/séance. Un relevé apprenant est unique par feuille/apprenant et un relevé enseignant par feuille. La feuille suit `draft → validated`; une correction ne crée pas de troisième état et reste une ligne append-only structurée. Le futur `offline_sync_commands` n’est pas créé en Phase 5.

## Conventions

- PostgreSQL, encodage UTF-8.
- UUIDv7 pour les identifiants exposables ; aucune sécurité fondée sur leur imprédictibilité.
- `center_id NOT NULL` sur toute donnée métier appartenant à un centre.
- Instants en `timestamptz` UTC ; date/heure locale calculée selon le fuseau du centre, défaut `Africa/Douala`.
- Montants XAF en `bigint` avec devise explicite ; aucun `float`.
- États avec enum applicative et contrainte SQL vérifiable lorsque stable.
- Archivage explicite pour apprenants/groupes ; pas de soft-delete générique sur les écritures financières et d'audit.
- Colonnes JSON seulement pour snapshots, payloads fournisseurs expurgés, métadonnées variables ou valeurs d'audit ; jamais pour éviter une relation métier stable.

## Carte des données

### Plateforme et identité

```text
centers
center_settings
users
center_memberships (user_id, center_id, status)
roles (center_id nullable pour modèles système)
permissions
role_permissions
membership_roles
authentication_events
```

Un utilisateur métier possède une seule adhésion active en V1 ; le modèle autorise une évolution contrôlée. Un Super Admin plateforme n'a pas besoin d'adhésion tenant. Email et téléphone normalisés sont des identifiants de connexion potentiels, avec vérification et unicité définies en Phase 2.

### Apprenants, inscriptions et groupes

```text
students
student_contacts (si contacts/tuteurs multiples confirmés)
enrollments
languages
levels
groups
student_group_assignments
student_level_histories
```

`students.group_id` et `students.level_id` ne doivent pas dupliquer la vérité historique. L'affectation active et l'historique de niveau sont les sources ; une projection peut accélérer les listes. Le groupe reste nullable avant placement malgré le tableau du PDF qui le présente dans la fiche obligatoire.

### Planning

```text
rooms
course_sessions
course_session_changes
```

Les chevauchements enseignant/salle/groupe sont interdits pour les séances actives. PostgreSQL pourra utiliser des plages `tstzrange` et contraintes d'exclusion, complétées par validation applicative claire. Les bornes sont `[début, fin)` : une séance peut commencer exactement à la fin d'une autre. Annulation et archivage sont des transitions, pas des suppressions.

### Présences

```text
attendance_sheets
attendance_records
teacher_attendances
attendance_corrections
offline_sync_commands
```

Une feuille est unique par séance et suit `DRAFT → VALIDATED → CORRECTED`. Un record apprenant est unique par feuille et apprenant. Une correction conserve ancienne/nouvelle valeur, auteur, motif, timestamps et version. `offline_sync_commands` impose une clé `(center_id, client_command_id)` unique.

### Pédagogie et évaluations

```text
question_banks
test_questions
question_options
placement_tests
test_sessions
test_answers
test_results
placement_decisions
assessments
assessment_components
assessment_results
bulletins
bulletin_documents
```

Une question système a `source=SYSTEM`, `editable=false` et aucun `center_id`; une question centre a `source=CENTER` et un `center_id`. Les règles de score/placement sont versionnées et référencées par chaque session afin de reproduire un résultat historique. Les coefficients d'évaluation suivent la même logique de version.

### Finance scolaire

```text
fee_catalog_items
invoices
invoice_items
payment_schedules
payment_schedule_items
payments
payment_allocations
payment_attempts
payment_transactions
payment_webhook_events
refunds
refund_allocations
receipts
```

Une facture porte son total et son solde dérivé des lignes, allocations de paiements confirmés et remboursements. Un paiement peut couvrir plusieurs lignes/échéances si la règle est confirmée. Les numéros de facture/reçu sont uniques par centre et séquence, générés sous verrou. Les lignes et confirmations sont immuables ; une correction passe par avoir/remboursement/écriture compensatrice.

### Communication, reporting et fichiers

```text
notification_templates
notification_consents
notification_logs
notification_deliveries
exports
stored_documents
```

Les logs séparent message logique et tentatives par canal. Les documents stockent métadonnées, checksum, classification, propriétaire tenant et clé de stockage privée, jamais une URL publique durable.

### SaaS et audit

```text
plans
plan_features
subscriptions
subscription_periods
subscription_payments
audit_logs
```

Les abonnements SaaS sont isolés de la finance apprenant. `audit_logs` est append-only et contient acteur, centre, action, sujet, anciennes/nouvelles valeurs expurgées, IP, user-agent, correlation ID et instant.

## Intégrité tenant

Pour chaque parent tenant-scoped, ajouter une unicité `(center_id, id)`. Les enfants utilisent une FK composite `(center_id, parent_id)` vers ce couple. Cela empêche qu'un enregistrement du centre A référence un parent du centre B même si le code applicatif se trompe. Les tables globales (langues/niveaux système, permissions, plans) sont explicitement listées et ne reçoivent pas de `center_id` artificiel.

Une Row Level Security PostgreSQL est prévue comme défense en profondeur après validation d'un mécanisme fiable `SET LOCAL app.current_center_id` dans toutes les transactions web/queue. Elle ne remplace ni scopes ni policies.

## Indexation initiale

- index commençant par `center_id` pour filtres tenant fréquents ;
- unicités métier tenant-scoped : numéros de facture/reçu, nom de groupe dans une période si requis, clés idempotentes ;
- index sur statuts actifs + échéances via index partiels ;
- index de recherche sur nom/téléphone normalisés après mesure ;
- index temporels sur séances, paiements, logs et notifications ;
- aucune indexation massive sans requête cible et `EXPLAIN`.

## Concurrence et transactions

Paiement confirmé, allocation, remboursement, séquence documentaire, validation/correction de présence et réservation de séance utilisent transaction SQL et verrouillage optimiste ou pessimiste explicite. Les callbacks externes sont enregistrés avant traitement. Les événements applicatifs sont émis après commit ; un outbox transactionnel sera ajouté si la fiabilité inter-processus mesurée l'exige.

## Rétention et sauvegarde

Les durées légales/commerciales ne sont pas définies. Par défaut de conception, finance et audit ne sont pas supprimés, mais leur durée finale et les règles d'anonymisation doivent être confirmées. Les sauvegardes seront chiffrées, quotidiennes, testées par restauration et assorties de RPO/RTO explicites.

## Table learners

learners est tenant-scoped par organization_id non nullable et expose un UUIDv7 unique. Les noms, date de naissance, téléphone normalisé, e-mail facultatif, langue, niveau CECRL initial et date d’inscription sont conservés avec un statut active ou archived.

Les contraintes SQL limitent le niveau à A1–C2, la langue active à de et le statut aux deux valeurs autorisées. Les index commencent par organization_id pour les listes, recherches, contacts et filtres. Le téléphone et l’e-mail ne sont pas uniques.

L’archivage conserve archived_at et archived_by. created_by et archived_by sont mis à NULL si l’identité disparaît ; la suppression d’une organisation cascade sur ses données tenant conformément au cycle de vie du tenant.

## Tables Phase 4

groups contient UUIDv7, organisation, nom, langue, niveau, capacité positive, adhésion enseignante responsable, statut et trace d’archivage. La FK (teacher_membership_id, organization_id) référence l’adhésion du même tenant.

group_learner_assignments est la source historique de composition. Les FK (organization_id, group_id) et (organization_id, learner_id) bloquent les références croisées. Un index unique partiel sur (organization_id, learner_id) lorsque detached_at est NULL garantit une seule affectation active ; les retraits renseignent acteur et timestamp sans supprimer la ligne.

course_sessions contient groupe, adhésion enseignante, salle textuelle facultative, starts_at, ends_at, statut et trace d’annulation. Les instants sont timestamptz UTC et la contrainte ends_at > starts_at complète les validations.

L’extension PostgreSQL btree_gist permet trois contraintes d’exclusion sur les séances scheduled :

- organisation + groupe + plage semi-ouverte [début, fin) ;
- organisation + adhésion enseignante + même plage ;
- organisation + salle normalisée en minuscules + même plage lorsque la salle est renseignée.

Les séances annulées ne participent plus aux exclusions. Les contraintes sont testées sur PostgreSQL réel ; SQLite n’est pas une cible de validation.
