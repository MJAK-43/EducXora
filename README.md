# EduXora

EduXora est le futur ERP/SaaS des centres de langue. Le dépôt contient actuellement le socle technique de la Phase 1 : Laravel 13, Vue 3, TypeScript strict, Inertia, PostgreSQL, Redis, Mailpit, Nginx et le design system initial. Aucun module métier, aucune authentification finale et aucun multi-tenant fonctionnel ne sont encore implémentés.

## Prérequis

- Git ;
- Docker Desktop avec Docker Compose v2 ;
- ports libres : `8080` (application), `5173` (Vite) et `8025` (Mailpit).

PHP, Composer, Node, PostgreSQL et Redis sont fournis par Docker. Le PHP local 8.2 n'est pas compatible avec Laravel 13 et ne doit pas servir de référence.

## Installation

```bash
git clone https://github.com/MJAK-43/EducXora.git
cd EducXora
cp .env.example .env
docker compose build
docker compose up -d
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

Sous PowerShell, remplacer `cp` par `Copy-Item .env.example .env`.

Ouvrir ensuite :

- application : <http://localhost:8080> ;
- catalogue UI local : <http://localhost:8080/dev/ui> ;
- Mailpit : <http://localhost:8025> ;
- healthcheck : <http://localhost:8080/up>.

Le service `node` lance Vite en mode développement. Après le premier démarrage, attendre que `docker compose logs node` indique que Vite est prêt.

## Configuration

`.env.example` documente l'URL, les ports, PostgreSQL, Redis, SMTP/Mailpit, cache, sessions et queues. Les valeurs fournies sont exclusivement locales. Pour éviter un conflit de ports :

```dotenv
APP_PORT=8090
VITE_PORT=5174
MAILPIT_PORT=8026
APP_URL=http://localhost:8090
```

Après toute modification de configuration :

```bash
docker compose exec app php artisan optimize:clear
```

En production, utiliser notamment `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, un `APP_KEY` secret et des credentials distincts injectés par la plateforme. Ne jamais committer `.env`.

## Commandes quotidiennes

```bash
docker compose up -d
docker compose ps
docker compose logs -f app nginx node
docker compose down
```

Les sources sont montées dans les conteneurs. `vendor`, `node_modules`, `storage` et le cache Bootstrap utilisent des volumes nommés pour garantir les droits d'écriture sur Windows.

### Base de données

Ces commandes détruisent ou modifient la base configurée : ne les exécuter que sur un environnement local/test dédié.

```bash
docker compose exec app php artisan migrate
docker compose exec app php artisan migrate:fresh
docker compose exec app php artisan migrate:rollback
```

La base `eduxora_testing` est créée automatiquement au premier démarrage du volume PostgreSQL et réservée aux tests.

### Diagnostic local

```bash
docker compose exec app php artisan about
docker compose exec app php artisan eduxora:foundation-check
docker compose exec app php artisan eduxora:foundation-check --mail
```

L'option `--mail` envoie uniquement vers Mailpit. La commande est refusée hors environnements `local` et `testing`.

## Tests et qualité

Backend :

```bash
docker compose exec app composer check
docker compose exec app php artisan test
docker compose exec app ./vendor/bin/pint --test
docker compose exec app ./vendor/bin/phpstan analyse
```

Frontend :

```bash
docker compose exec node npm run lint
docker compose exec node npm run format:check
docker compose exec node npm run typecheck
docker compose exec node npm run test
docker compose exec node npm run build
```

Avec Node 24 installé localement, les mêmes scripts NPM peuvent être exécutés depuis l'hôte après `npm ci`.

## Build de production

```bash
npm ci
npm run build
docker build --target production -t eduxora:local .
```

Vite génère les assets versionnés dans `public/build`. L'image de production compile les assets, installe les dépendances Composer sans paquets de développement et exécute PHP-FPM avec un utilisateur non-root.

## CI

`.github/workflows/ci.yml` exécute deux jobs sur les pushes ciblés et les pull requests vers `main` :

- PHP 8.4 + PostgreSQL 17 + Redis 7.4 : validation Composer, Pint, Larastan, migrations et tests Laravel ;
- Node 24 : ESLint, Prettier, `vue-tsc`, Vitest et build Vite.

## Architecture

Le produit suivra un monolithe modulaire Laravel. La Phase 1 ne crée que l'infrastructure partagée, l'App Shell et les composants UI génériques. Les décisions détaillées se trouvent dans :

- [architecture](docs/architecture.md) ;
- [développement](docs/development.md) ;
- [tests](docs/testing.md) ;
- [sécurité](docs/security.md) ;
- [design system](docs/design-system.md) ;
- [ADR](docs/decisions/README.md) ;
- [rapport de Phase 1](docs/phases/phase-1-report.md).

## Dépannage

- `Bind for 0.0.0.0 failed` : changer `APP_PORT`, `VITE_PORT` ou `MAILPIT_PORT` dans `.env`, puis relancer Compose.
- Page sans styles ou erreur Vite : vérifier `docker compose logs node` et que le port `VITE_PORT` est accessible.
- Erreur de clé : lancer `docker compose exec app php artisan key:generate`.
- Base de test absente sur un ancien volume : créer `eduxora_testing` manuellement ou recréer uniquement le volume PostgreSQL local après sauvegarde explicite.
- État détaillé : `docker compose ps` puis `docker compose logs --tail=200 <service>`.

## Limite de phase

La prochaine phase (authentification et multi-tenancy) ne doit commencer qu'après autorisation explicite. Les fichiers framework `User` et migrations Laravel standard ne constituent pas une implémentation métier.
