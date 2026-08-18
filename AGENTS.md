# Instructions de contribution — EduXora

## Autorité et périmètre

Lire avant toute modification :

1. `EduXora_CahierDesCharges.pdf` ;
2. `docs/requirements.md` ;
3. les ADR applicables dans `docs/decisions/` ;
4. le rapport et le backlog de la phase demandée.

Ne jamais commencer une phase sans demande explicite. Ne jamais étendre silencieusement le périmètre. Une règle ambiguë doit devenir une question documentée avec une hypothèse réversible.

## Architecture cible

EduXora est un **monolithe modulaire Laravel 13** avec Vue 3/TypeScript/Inertia. Les domaines vivent sous `app/Domain/<Domain>` et exposent des contrats explicites. Les contrôleurs restent minces : validation HTTP, autorisation, appel d'une Action/Query, transformation de réponse. Les modèles Eloquent décrivent la persistance et les relations, pas des workflows complexes.

Structure cible à introduire progressivement à partir des phases métier :

```text
app/
  Domain/{Center,Identity,Student,Learning,Scheduling,Attendance,
          Assessment,Finance,Payment,Communication,Reporting,
          Subscription,Audit}/
  Support/{Tenancy,Idempotency,Money,Clock,Files}/
  Http/{Controllers,Middleware,Requests,Resources}/
resources/js/
  Components/ Layouts/ Pages/ Composables/ Types/
resources/css/
tests/{Unit,Feature,Integration,Architecture,Browser}/
```

Un domaine peut contenir `Actions`, `Contracts`, `Data`, `Enums`, `Events`, `Jobs`, `Models`, `Policies`, `Queries` et `Services`. N'ajouter une couche que lorsqu'elle porte une responsabilité réelle.

## Règles multi-tenant obligatoires

- Le centre courant vient exclusivement de l'identité authentifiée et de son adhésion serveur, jamais d'un `center_id` client.
- Toute table métier tenant-scoped porte `center_id` non nullable, sauf exception documentée.
- Utiliser le `TenantContext`, le scope central et les policies ; aucun `where('center_id', ...)` improvisé dans les contrôleurs.
- Les relations entre tables tenant-scoped utilisent des contraintes composites incluant `center_id` lorsque cela empêche une référence croisée.
- Les jobs, caches, verrous, fichiers, exports, broadcasts et logs propagent/segmentent le contexte du centre.
- Toute échappatoire Super Admin est explicite, auditée et couverte par test. Aucun `withoutGlobalScopes()` non justifié.
- Chaque nouvelle ressource tenant-scoped exige des tests Centre A → ressource Centre B pour lecture, écriture, suppression, téléchargement et export lorsque ces opérations existent.

## Autorisation et sécurité

- Authentification et visibilité UI ne remplacent jamais une policy backend.
- Autoriser via permissions atomiques et rôle d'adhésion au centre. Le Super Admin plateforme est hors tenant.
- Utiliser des Form Requests, listes blanches de mass assignment, sorties échappées et sérialisation minimale.
- CSRF sur le web ; signatures/secrets et idempotence sur webhooks ; rate limiting sur login, reset, exports, messages et paiements.
- Ne jamais journaliser secret, mot de passe, PIN, token, payload fournisseur sensible ni document complet.
- Cookies `Secure`, `HttpOnly`, `SameSite=Lax` au minimum ; timeout d'inactivité 30 minutes et durée absolue 24 heures.
- Les documents privés passent par autorisation serveur ou URL signée courte, jamais par un disque public prévisible.
- Les opérations financières confirmées, remboursements et corrections d'audit sont append-only ; pas de suppression destructive.

## Finance et intégrations

- `Invoice`, `PaymentSchedule`, `Payment`, `PaymentAttempt`, `PaymentTransaction`, `Refund` et `Receipt` sont des concepts distincts.
- Stocker les montants XAF en entier (`bigint`), jamais en flottant.
- Un paiement externe n'existe comme paiement confirmé qu'après confirmation authentifiée du fournisseur.
- Toute transition financière sensible s'exécute dans une transaction SQL avec verrouillage adapté.
- Les webhooks ont une clé d'événement fournisseur unique et sont rejouables sans double paiement.
- SMS, WhatsApp, email, génération lourde de documents et appels fournisseur passent par queue avec retries bornés et dead-letter/alerte.

## Frontend et design system

- Vue 3 Composition API avec `<script setup lang="ts">` et TypeScript strict.
- L'état serveur reste dans Inertia ; Pinia seulement pour un état global client durable réellement partagé.
- Pas de logique métier sensible dans Vue. Le frontend peut prévisualiser, le serveur décide.
- Utiliser les tokens et composants de `docs/design-system.md`. Aucune palette ou valeur arbitraire répétée.
- Tailwind CSS 4 est interdit tant que la cible iOS 13 demeure, car son socle exige Safari 16.4+.
- Labels visibles, focus perceptible, erreurs associées aux champs, navigation clavier et information jamais portée par la couleur seule.
- Tester au minimum 375, 390, 768, 1024, 1280 et 1440 px. Les tableaux complexes deviennent cartes/listes ou vues prioritaires sur mobile.

## Offline

Le mode offline est limité au pointage prévu. IndexedDB contient le minimum, avec expiration et purge à la déconnexion. Chaque commande possède UUID, version de base et horodatage client. Le serveur reste autoritaire, garantit l'idempotence et retourne explicitement les conflits. Ne jamais afficher « synchronisé » avant acquittement serveur.

## Conventions de code et données

- PHP : types stricts, PSR-12/Pint, enums pour états finis, horloge injectée dans les règles temporelles.
- TypeScript : `strict: true`, pas de `any` implicite, contrats générés ou partagés de façon contrôlée.
- Identifiants métier exposés : UUIDv7. Temps persistés en UTC, affichés dans le fuseau du centre (`Africa/Douala` par défaut).
- Noms de permissions : `<resource>.<action>` ; événements : passé métier (`PaymentConfirmed`).
- Migrations avec FK, index, unicité, stratégie `on delete` explicite et rollback testé.
- Pas de valeurs d'état magiques répétées, pas de requête N+1, pas de dépendance d'un domaine sur les détails internes d'un autre.

## Installation et démarrage

Docker est la référence, car le PHP hôte 8.2 n'est pas compatible avec Laravel 13.

```bash
Copy-Item .env.example .env          # PowerShell ; utiliser cp sous Unix
docker compose build
docker compose up -d
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

Application : `http://localhost:8080`, catalogue local : `/dev/ui`, Mailpit : `http://localhost:8025`. Les ports peuvent être surchargés dans `.env`.

## Commandes et qualité

Backend :

```bash
docker compose exec app composer validate --strict
docker compose exec app ./vendor/bin/pint --test
docker compose exec app ./vendor/bin/phpstan analyse
docker compose exec app php artisan test
docker compose exec app composer check
```

Frontend :

```bash
npm ci
npm run lint
npm run format:check
npm run typecheck
npm run test
npm run build
```

Migrations sur une base locale/test dédiée uniquement :

```bash
docker compose exec app php artisan migrate:fresh --force
docker compose exec app php artisan migrate:rollback --force
docker compose exec app php artisan migrate --force
```

Diagnostic des services :

```bash
docker compose ps
docker compose exec app php artisan eduxora:foundation-check
docker compose exec app php artisan eduxora:foundation-check --mail
```

Un changement UI exige au minimum lint, format, typecheck, Vitest et build. Un changement PHP exige Pint, Larastan et les tests Laravel. Ajouter les tests de migration, d'architecture ou navigateur lorsque le risque le justifie.

## Definition of Done

Une modification est terminée lorsque : règle métier, validation, permission, isolation tenant, erreurs/loading/empty states, accessibilité, responsive, tests adaptés, lint, analyse statique, typecheck, build, migrations/rollback, sécurité et documentation sont validés. Aucun test ne doit être désactivé pour verdir le pipeline.

## Git et secrets

Branches `feature/*`, `fix/*`, `refactor/*`, `chore/*`. Commits Conventional Commits (`feat:`, `fix:`, `test:`, `refactor:`, `docs:`, `chore:`). Préserver les changements utilisateur. Ne jamais committer `.env`, credentials, clés privées, tokens, exports contenant des données personnelles ou données de production.

## Interdictions

Pas de contrôleur massif, de logique métier complexe dans Vue/Model, de `center_id` accepté aveuglément, de secret codé en dur, de règle dupliquée, de reçu avant confirmation, de mutation financière destructive, de webhook non idempotent, de composant copié-collé, de données fictives en production ni de TODO critique non signalé.
