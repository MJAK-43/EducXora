# Stratégie de tests

## Couverture réellement active en Phase 1

Le backend utilise PHPUnit avec PostgreSQL et Redis réels dans Docker/CI. `tests/Feature/FoundationTest.php` couvre le rendu Inertia de `/`, `/up`, la protection environnementale de `/dev/ui`, la page 404 sûre, les connexions PostgreSQL/Redis et la commande de diagnostic. `RefreshDatabase` s'exécute sur la base dédiée `eduxora_testing`.

Le frontend utilise Vitest, jsdom et Vue Test Utils. Les tests de composants couvrent actuellement `UiButton`, `UiInput` et `UiAlert` : rendu, variantes/erreurs, disabled, loading et événements. La vérification statique repose aussi sur ESLint, Prettier et `vue-tsc` en mode strict.

Commandes de référence :

```bash
docker compose exec app php artisan test
npm run test
npm run lint
npm run format:check
npm run typecheck
npm run build
```

Le cycle PostgreSQL bloquant est `migrate:fresh --force`, `migrate:rollback --force`, puis `migrate --force`, exclusivement sur une base locale/test dédiée.

L'automatisation navigateur n'était pas disponible lors de la validation Phase 1. Les largeurs 375, 390, 768, 1024, 1280 et 1440 px ont donc le statut **NOT TESTED visuellement** ; elles restent une vérification manuelle obligatoire avant toute validation UX de production. Les media queries, la compilation et la structure accessible ont été vérifiées sans moteur de rendu.

## Couverture ajoutée en Phase 2

Les suites `AuthenticationTest`, `TenancyAndRbacTest` et `InvitationAndAuditTest` vérifient l’inscription atomique, le hash du mot de passe, la connexion active/inactive, le reset non énumérant, l’expiration absolue, les UUID, les adhésions et rôles initiaux. Elles couvrent aussi la tentative d’accès tenant A/B, le changement vers une organisation non liée, les permissions Manager/Admin, la suspension d’un tenant, un rôle cross-tenant, les invitations hashées/expirées/non rejouables et l’audit append-only expurgé.

La suite active totalise 21 tests backend / 77 assertions et 8 tests frontend. PostgreSQL et Redis réels sont utilisés. Le typecheck Vue, ESLint, Prettier, Larastan, Pint et le build Vite sont bloquants. La vérification navigateur responsive Phase 2 est **NOT TESTED** dans cette session faute de Node REPL exposé ; la CI GitHub distante est **NOT TESTED** car aucun commit/push ne fait partie de cette livraison.

## Couverture Phase 5

`AttendanceManagementTest` couvre roster historique serveur, feuille unique, séance annulée, les statuts présent/absent/excusé, injection d’apprenant ou d’enseignant, brouillon, présence enseignant, validation atomique, double validation, verrouillage, corrections motivées, audit, historiques, taux et matrice tenant A/B. Il couvre aussi le périmètre propre de l’enseignant et le refus du rôle Accountant.

`AttendancePages.spec.ts` vérifie le rendu des trois statuts, la soumission du brouillon, l’état validé/verrouillé et la correction motivée vers la feuille ciblée. Les chiffres globaux sont consignés dans `docs/phases/phase-5-report.md` après la gate complète.

La validation visuelle réelle à 375, 390, 768, 1024, 1280 et 1440 px ainsi que Safari iOS 13 reste **NOT TESTED**.

## Principes

Tester les invariants au niveau le moins coûteux, puis couvrir les frontières réellement risquées. Tous les tests utilisent PostgreSQL pour les comportements qui en dépendent ; SQLite n'est pas un substitut pour FK composites, contraintes d'exclusion, verrouillage ou RLS.

## Pyramide

### Unitaires

- calcul de solde, statut d'échéance, allocations et remboursements ;
- transitions d'état paiement/présence/notification ;
- règles de placement versionnées et moyennes/coefficient ;
- détection de chevauchement en mémoire avant contrainte DB ;
- normalisation téléphone/date/devise ;
- résolution de conflit offline et idempotence ;
- permissions et matrice de rôles pure.

### Feature

- parcours HTTP/Inertia, Form Requests, policies, CSRF et rate limits ;
- pagination, filtres, exports et téléchargements autorisés ;
- login/logout/reset/session ;
- erreurs métier stables et absence de données sensibles dans les réponses.

### Intégration

- migrations/rollback PostgreSQL, FK composites, contraintes et concurrence ;
- Redis queues/cache/verrous avec préfixes tenant ;
- providers Mobile Money/SMS/WhatsApp/email simulés par contrats ;
- webhooks signés, dupliqués, désordonnés et rejoués ;
- génération et contrôle de PDF/Excel ;
- stockage privé et URLs signées.

### Composants frontend

Vitest + Vue Test Utils pour états loading/error/empty, formulaires, DataTable responsive, navigation clavier et annonces accessibles. Les règles métier ne sont pas réimplémentées dans ces tests.

### E2E

Playwright sur parcours critiques, dont la chaîne prioritaire : création centre → utilisateur → apprenant → test → groupe → séance → présence → facture → paiement → reçu → évaluation → bulletin. Les phases n'activent que la portion livrée ; le parcours complet devient bloquant avant bêta.

## Matrice obligatoire multi-tenant

Pour `students`, `groups`, `course_sessions`, `attendance`, `invoices`, `payments`, `bulletins`, `reports` et `documents`, un utilisateur du centre A tente sur une ressource B :

| Opération                | Résultat attendu                                     |
| ------------------------ | ---------------------------------------------------- |
| liste/recherche          | ressource absente, aucune fuite de compteur          |
| vue directe              | 404 de préférence, 403 accepté si politique homogène |
| modification             | 404/403, aucune mutation                             |
| suppression/archivage    | 404/403, aucune mutation                             |
| téléchargement/export    | 404/403 même avec ancienne URL signée                |
| identifiant dans payload | ignoré/rejeté, jamais de changement de tenant        |

Ajouter des cas job, cache, broadcast et fichier. L'accès Super Admin est testé séparément et doit produire un audit.

## Finance et concurrence

- deux callbacks identiques créent exactement un effet financier ;
- callbacks succès/échec désordonnés respectent la machine d'état ;
- deux validations simultanées ne dépassent pas le montant autorisé ;
- remboursement total/partiel reste borné au net encaissé remboursable ;
- reçu unique seulement après confirmation ;
- rollback atomique si une étape échoue ;
- représentation XAF exacte à grands montants.

## Offline

Scénarios : perte réseau pendant saisie, double clic, redémarrage navigateur, commande dupliquée, séance modifiée/annulée, feuille déjà validée ailleurs, droits retirés, token expiré, resynchronisation partielle, ordre différent et stockage arrivé à quota. Un conflit reste visible jusqu'à résolution et aucune commande n'est perdue silencieusement.

## Accessibilité, responsive et compatibilité

- tests automatisés axe sur pages clés, complétés par navigation clavier et lecteur d'écran manuels ;
- focus, labels, erreurs, modales, annonces live et contraste vérifiés ;
- captures/tests à 375, 390, 768, 1024, 1280 et 1440 px ;
- tests réels ou service navigateur pour Safari iOS 13, Safari récent, Chrome, Firefox et Edge ;
- profils lent/3G et CPU mobile sur pages principales.

## Performance

Budgets initiaux à préciser en Phase 1 : réponse serveur p95, LCP p75 sous 3G, taille JS initiale, nombre de requêtes DB et durée des exports. Tests de charge avec 50 sessions simultanées sur dashboard, pointage et encaissement. Les seuils deviennent bloquants quand une baseline reproductible existe.

## Données de test

Factories déterministes, horloge figée et tenants distincts par défaut. Aucun test ne dépend d'API réelle ni de production. Les secrets de test sont factices. Les tests de provider utilisent des fixtures documentées sans données personnelles.

## Portes CI

Formatage, analyse statique, tests unitaires/feature/intégration, lint, typecheck, tests frontend et build sont obligatoires. Une suite nocturne peut porter E2E multi-navigateurs, charge, scans et restauration ; les parcours MUST restent bloquants avant livraison.

## Couverture Phase 3

LearnerManagementTest vérifie validation, normalisation du téléphone, création, recherche, pagination, filtres, rôles, archivage/restauration, audit, stockage privé et export. Le scénario inter-tenant prouve que A ne peut pas consulter, modifier, archiver, restaurer, télécharger la photo ni exporter l’apprenant de B.

PhoneNormalizerTest couvre les formats local, international +237 et 00237. LearnerForm.spec.ts vérifie les libellés accessibles, les contraintes photo et les soumissions création/édition multipart.

La migration Learner doit être testée sur eduxora_testing par la séquence fresh → rollback de la dernière migration → migrate. Ne jamais exécuter cette séquence sur une base de développement partagée ou de production.

## Couverture Phase 4

GroupManagementTest couvre création, liste paginée, recherche/filtres, modification, changement d’enseignant, archivage/restauration, capacité, ajout/retrait, historique, compatibilité, duplication, rôles et matrice tenant A/B.

CourseSessionManagementTest couvre fuseau organisation/UTC, création, édition, annulation, semaine/filtres, groupe archivé, créneaux adjacents et conflits groupe/enseignant/salle. Il vérifie aussi la lecture propre à l’enseignant et les tentatives d’édition, annulation ou référence de ressources du tenant B.

PhaseFourComponents.spec.ts couvre les libellés/contraintes des formulaires, création/édition, gestion des membres, confirmation de retrait, sept jours, états vides, cartes complètes et séance annulée sans actions.

La suite validée totalise :

- 51 tests Laravel et 306 assertions, dont 19 tests Phase 4 ;
- 24 tests Vitest dans 5 fichiers, dont 13 tests Phase 4.

Les migrations Phase 4 sont validées par migrate:fresh --force, migrate:rollback --force, puis migrate --force sur PostgreSQL dédié. La validation navigateur aux six largeurs cibles reste **NOT TESTED** dans cette session.
