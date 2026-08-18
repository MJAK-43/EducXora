# Développement, environnements et CI/CD

## Référence technique Phase 1

La stack exécutable est fournie par Docker Compose : PHP 8.4, Laravel 13, Node 24, PostgreSQL 17, Redis 7.4, Nginx et Mailpit. Le code frontend utilise Vue 3, TypeScript strict, Inertia, Vite et CSS/PostCSS/Autoprefixer. Les versions exactes des paquets sont verrouillées par `composer.lock` et `package-lock.json`.

## Services locaux

| Service | Rôle | Port hôte par défaut |
|---|---|---:|
| `nginx` | entrée HTTP | 8080 |
| `app` | PHP-FPM Laravel | interne |
| `node` | Vite/HMR | 5173 |
| `postgres` | données développement et test | interne |
| `redis` | cache, sessions, queue, verrous | interne |
| `queue` | worker Laravel | interne |
| `scheduler` | scheduler Laravel | interne |
| `mailpit` | SMTP captif et UI | 8025 |

Tous communiquent sur `eduxora_internal`. PostgreSQL et Redis ne sont pas publiés sur l'hôte. Les données persistantes, dépendances et répertoires runtime utilisent des volumes nommés.

## Installation et exploitation locale

Voir le [README](../README.md) pour le parcours complet. Commandes essentielles :

```bash
docker compose build
docker compose up -d
docker compose ps
docker compose logs -f app nginx node
docker compose down
```

Le healthcheck public `/up` confirme uniquement que l'application répond. La commande locale `php artisan eduxora:foundation-check` contrôle PostgreSQL et Redis ; `--mail` ajoute un envoi vers Mailpit sans exposer de diagnostic sensible publiquement.

## Scripts stables

Composer :

```text
composer lint       Pint en mode vérification
composer analyse    Larastan/PHPStan niveau 6
composer test       tests Laravel
composer check      lint + analyse + tests
```

NPM :

```text
npm run dev
npm run lint
npm run format
npm run format:check
npm run typecheck
npm run test
npm run test:watch
npm run build
```

## Environnements

| Environnement | Données | Services externes | But |
|---|---|---|---|
| development | locales/synthétiques | fakes et Mailpit | développement |
| testing | base `eduxora_testing` dédiée | Mail array/fakes | tests automatisés |
| staging | synthétiques, jamais copie brute prod | sandboxes | recette |
| production | réelles | comptes production | exploitation |

Chaque environnement doit posséder base, Redis, stockage, secrets et callbacks propres. `.env.example` ne contient que des valeurs locales factices.

## Pipeline GitHub Actions

Le workflow `.github/workflows/ci.yml` se déclenche sur pull request vers `main` et push sur `main`, `feature/**`, `fix/**`, `refactor/**` et `chore/**`.

```text
backend
  Composer validate/install
  Laravel about
  Pint
  Larastan
  migrate:fresh -> rollback -> migrate
  tests Laravel avec PostgreSQL et Redis

frontend
  npm ci
  ESLint
  Prettier check
  vue-tsc
  Vitest
  build Vite
```

Les jobs n'utilisent ni `continue-on-error` ni SQLite. La protection de branche et les scans renforcés dépendront de la configuration GitHub/production et sont hors du seul dépôt local.

## Image de production

Le `Dockerfile` multi-stage sépare dépendances PHP, environnement de développement, build frontend et image de production. La cible `production` :

- compile les assets avec Node 24 ;
- installe Composer avec `--no-dev` ;
- ne contient pas de cache de build NPM ;
- exécute PHP-FPM avec un utilisateur non-root ;
- attend la configuration et les secrets à l'exécution.

Le déploiement cible restera : une image immuable, migration contrôlée, web/workers/scheduler, smoke tests puis promotion. L'orchestration de production sera décidée lorsque l'hébergeur sera connu.

## Git

Branche principale `main`, branches `feature/*`, `fix/*`, `refactor/*`, `chore/*`, commits conventionnels. Ne jamais versionner `.env`, secrets, dumps, exports personnels, `vendor`, `node_modules`, caches, coverage ou assets HMR temporaires.
