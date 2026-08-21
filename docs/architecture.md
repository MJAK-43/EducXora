# Architecture cible

## Fondation applicative Phase 2

La requête web authentifiée traverse successivement les contrôles utilisateur actif, durée absolue, e-mail vérifié, résolution de l’adhésion, état de l’adhésion et état de l’organisation. `TenantContext` est un service scoped à la requête. Les modèles tenantés lisent ce contexte via un scope global fail-closed et l’utilisent pour attribuer `organization_id` à la création.

L’identité `User` reste globale ; les rôles métier sont attachés à `OrganizationMembership`. `MembershipAuthorizer` résout les permissions effectives et alimente Gates/policies. Le Super Admin emprunte un groupe de routes plateforme séparé sans fabriquer d’adhésions tenant.

Les services `OrganizationCreator`, `InvitationService`, `RoleProvisioner` et `AuditLogger` concentrent les transactions et invariants sensibles. Les contrôleurs Inertia orchestrent validation et réponse, sans porter la logique de tenant ou de rôle.

Statut : architecture cible validée en Phase 0 ; fondation technique implémentée en Phase 1. Les domaines métier restent soumis aux phases et ADR ultérieurs.

## Socle implémenté en Phase 1

```text
Navigateur
  -> Nginx
     -> Laravel 13 / PHP-FPM 8.4
        -> Inertia -> Vue 3 + TypeScript strict + Vite
        -> PostgreSQL 17
        -> Redis 7.4 (cache, session, queue)
        -> Mailpit (SMTP local)

Workers : queue Laravel + scheduler, basés sur la même image PHP
```

Le `Dockerfile` multi-stage produit une cible de développement et une cible de production. Docker Compose isole les services, persiste PostgreSQL/Redis et utilise des volumes runtime pour garantir les droits d'écriture sous Windows. `/up` est le healthcheck public minimal ; `eduxora:foundation-check` réalise les diagnostics internes locaux.

Le frontend Phase 1 se limite à `resources/js/Components/Ui`, `resources/js/Layouts`, la page temporaire `Home` et le catalogue local `Dev/UiKit`. Aucun dossier de domaine, modèle centre, tenant middleware, rôle, permission ou route métier n'a été ajouté.

## Domaine Attendance — Phase 5

`app/Domain/Attendance` réutilise `CourseSession` comme unique représentation d’une séance. Une feuille tenant-scoped photographie les références groupe/enseignant et son roster historique au démarrage. Les mutations passent par des Actions transactionnelles ; la validation verrouille définitivement le brouillon et les modifications suivantes passent par une correction métier append-only.

Les historiques sont bornés par période et paginés. Les indicateurs Groupe et le taux apprenant sont calculés côté serveur sur les seules feuilles validées et séances non annulées. L’enseignant reste limité aux séances de sa propre adhésion ; les rapports tenant complets exigent `attendance.view_reports`. Voir ADR-016.

## Principes directeurs

1. Isolation tenant et autorisation avant ergonomie.
2. Cohérence financière et traçabilité avant débit fonctionnel.
3. Monolithe modulaire pour livrer vite sans distribuer prématurément la complexité.
4. APIs fournisseurs derrière des contrats ; aucune dépendance directe depuis les contrôleurs.
5. Serveur autoritaire, y compris pour les calculs prévisualisés et la synchronisation offline.
6. Évolution par migrations et événements compatibles, sans big bang.

## Vue de contexte

```text
Utilisateurs centre ─┐
Super Admin SaaS ────┼─ HTTPS ─> Nginx ─> Laravel + Inertia/Vue
Présence offline ────┘                         │
                                               ├─ PostgreSQL (source de vérité)
                                               ├─ Redis (queue/cache/session/verrous)
                                               ├─ Object storage privé (PDF/photos/exports)
                                               └─ Workers ─┬─ MTN / Orange
                                                           ├─ SMS / WhatsApp / SMTP
                                                           └─ génération PDF/Excel
Fournisseurs Mobile Money ── webhooks authentifiés ───────> Laravel
```

## Style d'architecture

Un déploiement applicatif unique contient des domaines fortement séparés. Chaque domaine possède ses règles, ses modèles et ses cas d'usage. Les appels synchrones entre domaines passent par des Actions/Queries ou contrats publics ; les effets secondaires passent par événements et jobs après commit. On n'introduit des microservices qu'en présence de mesures démontrant un besoin d'isolation de charge ou d'organisation.

### Domaines et responsabilités

| Domaine       | Responsabilité                                         | Entités principales                           |
| ------------- | ------------------------------------------------------ | --------------------------------------------- |
| Center        | cycle de vie et paramètres d'un centre                 | Center, CenterSetting, CenterBranding         |
| Identity      | identités, adhésions, rôles et permissions             | User, CenterMembership, Role, Permission      |
| Student       | dossier apprenant, inscription, archivage              | Student, Enrollment                           |
| Learning      | langues, niveaux, groupes et affectations              | Language, Level, Group, GroupAssignment       |
| Scheduling    | salles, séances, conflits, annulations                 | Room, CourseSession                           |
| Attendance    | feuilles, pointages, corrections, sync offline         | AttendanceSheet, AttendanceRecord, Correction |
| Assessment    | questions, tests, progression, notes, bulletins        | Question, TestSession, Assessment, Bulletin   |
| Finance       | factures, échéanciers, créances, reçus, remboursements | Invoice, Schedule, Payment, Refund, Receipt   |
| Payment       | orchestration et intégrations Mobile Money             | Attempt, Transaction, WebhookEvent, Provider  |
| Communication | modèles, consentements et livraisons multicanal        | Template, NotificationLog                     |
| Reporting     | projections, exports, KPI                              | ReportRun, Export                             |
| Subscription  | plans et abonnements du centre au SaaS                 | Plan, Subscription, SubscriptionPayment       |
| Audit         | journal immuable des opérations sensibles              | AuditLog                                      |

Finance scolaire et facturation SaaS partagent des primitives techniques (Money, idempotence) mais jamais leurs agrégats ni leurs tables métier.

## Organisation backend cible

```text
app/Domain/<Domain>/
  Actions/       cas d'usage transactionnels
  Contracts/     ports publics et providers
  Data/          DTO immuables aux frontières
  Enums/         états finis
  Events/        faits métier passés
  Jobs/          traitements asynchrones idempotents
  Models/        persistance et relations
  Policies/      autorisation ressource/action
  Queries/       lectures optimisées et paginées
  Services/      orchestration métier cohésive
app/Support/
  Tenancy/ Idempotency/ Money/ Clock/ Files/
```

Les contrôleurs HTTP ne démarrent pas leurs propres transactions métier et ne contactent aucun fournisseur. Les Form Requests valident la forme ; les Actions vérifient les invariants métier. Les API Resources/Inertia props exposent uniquement les champs nécessaires.

## Architecture frontend

Inertia maintient un seul produit web et évite une API CRUD dupliquée. Vue 3 gère interaction et rendu ; le backend demeure la source d'autorisation et de règles. Des endpoints JSON dédiés existent pour synchronisation offline, webhooks, polling fournisseur et, plus tard, un client mobile.

```text
resources/js/
  Components/{Base,DataDisplay,Forms,Feedback,Navigation}/
  Composables/
  Layouts/
  Pages/<Domain>/
  Types/
```

Les pages chargent des props paginées et minimales. Le cache client ne stocke pas de vérité financière. Pinia est réservé au statut de connectivité/synchronisation et à un éventuel état transversal démontré.

## Flux asynchrones

- Après commit seulement : notification, export, génération de document et intégration fournisseur.
- Chaque job porte `center_id`, `correlation_id`, clé d'idempotence, nombre de tentatives et backoff.
- Files séparées : `critical-payments`, `notifications`, `documents`, `exports`, `default`.
- Échecs définitifs conservés, alertés et rejouables manuellement sans dupliquer l'effet.
- Scheduler : relances d'impayés, résumés quotidiens, expiration d'intentions, nettoyage d'exports et contrôles de santé.

## Déploiement cible

Développement via Docker Compose : Nginx, PHP-FPM, PostgreSQL, Redis, worker, scheduler et Mailpit. Recette/production utilisent la même image applicative immuable, une configuration injectée, des services persistants managés ou sauvegardés, plusieurs workers et stockage objet privé. Les migrations sont un job contrôlé avant bascule.

## Objectifs non fonctionnels

- pages principales < 3 s sur profil réseau 3G, avec budget détaillé en Phase 1 ;
- 50 utilisateurs simultanés au minimum, testés sur parcours représentatifs ;
- disponibilité cible 99 %, sous réserve de RTO/RPO à confirmer ;
- français, dates `JJ/MM/AAAA`, devise XAF entière, téléphone camerounais normalisé E.164 ;
- desktop/mobile et iOS 13+ ;
- WCAG 2.2 AA lorsque raisonnablement applicable.

## Dépendances de livraison

Identity/Tenancy précède tous les domaines tenant-scoped. Student/Learning précède Scheduling ; Scheduling précède Attendance ; Student précède Finance et Assessment. Communication consomme les événements des autres domaines. Reporting ne devient jamais une voie détournée contournant leurs policies.

Voir les ADR pour les décisions et `docs/requirements.md` pour les hypothèses ouvertes.

## Domaine Learner — Phase 3

Le premier domaine métier est app/Domain/Learner. Il contient les enums, le modèle tenant-scoped, les Actions de mutation, la Query de liste, le stockage de photo et le presenter Inertia. Les contrôleurs HTTP ne font que l’autorisation de ressource, l’orchestration et la transformation de réponse.

Les paramètres de route Learner restent des UUID simples puis sont résolus dans le contrôleur après le middleware tenant. Ce choix garantit que le scope fail-closed dispose du TenantContext avant toute requête Eloquent.

Les relations vers les groupes, le planning, les présences, les évaluations et la finance sont volontairement absentes. Elles seront introduites uniquement dans leurs phases métier.

## Domaines Learning et Scheduling — Phase 4

app/Domain/Learning porte les groupes, leur cycle de vie, les affectations historiques, les Actions transactionnelles, les Queries et les présentations Inertia. L’enseignant responsable est une adhésion active du même tenant, contrôlée par une FK composite et sélectionnée par UUID.

app/Domain/Scheduling porte les séances, leur annulation, la conversion du fuseau de l’organisation vers UTC, la vue hebdomadaire et la détection des conflits. Les contrôleurs restent des adaptateurs HTTP ; les invariants de capacité, d’éligibilité et de créneau vivent dans les Actions/Services.

Les enseignants reçoivent uniquement les lectures liées à leur teacher_membership_id. Les profils administratifs disposant de group.update ou schedule.create voient le périmètre tenant complet. Les UUID de route sont résolus après le middleware tenant afin de conserver le comportement fail-closed.

Les collisions sont défendues deux fois : requête applicative pour le message utilisateur, puis contraintes d’exclusion PostgreSQL pour les écritures concurrentes. L’annulation change l’état et libère les contraintes ; aucune suppression de séance n’est exposée.
