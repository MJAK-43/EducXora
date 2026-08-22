# Rapport de Phase 6 — Suivi pédagogique & Test de niveau

- Date : 2026-08-22
- Base : `1b794599aa5c4173bdddd7272ec12b98abd715ce`, tag `v0.5.0`
- Périmètre : banque de questions allemande, positionnement A1–C2, décision humaine, niveau courant et historique
- Git : aucun commit, tag, push, merge, rebase ou stash créé/supprimé

## 1. Diagnostic initial

La Phase 5 était propre et validée. Le domaine Learner possédait seulement `initial_level`; Learning imposait la compatibilité de niveau lors d’une affectation et verrouillait la capacité. Aucun modèle, route, écran, contenu ou fixture de question de positionnement n’existait. Les trois stashes de sécurité étaient présents.

Le nouveau domaine réutilise `TenantContext`, `BelongsToTenant`, les FK composites, `MembershipAuthorizer`, les policies, `AuditLogger`, `Clock`, les groupes Phase 4 et les composants Vue existants. Il ne reconstruit aucun domaine validé.

## 2. Modèle fonctionnel

Le système distingue quatre vérités :

1. la question source, globale système ou propre à une organisation ;
2. l’instantané immuable présenté dans une tentative ;
3. la suggestion automatique issue du score ;
4. la décision humaine validée et l’historique du niveau courant.

`initial_level` reste la valeur d’entrée. `current_level` devient la valeur pédagogique opérationnelle. Un groupe n’est jamais choisi depuis un identifiant de tenant client ; il est résolu sous le scope du tenant courant et revérifié dans l’Action.

## 3. Banque de questions

- Langue V1 : allemand `de` uniquement.
- Niveaux : A1, A2, B1, B2, C1, C2.
- Choix : exactement A, B, C, D ; bonne réponse obligatoire.
- Source `system` : `organization_id` nul, visible par les tenants autorisés, lecture seule, sans route de création/modification/désactivation.
- Source `organization` : tenant obligatoire, CRUD limité à création, lecture, modification et désactivation.
- Suppression : aucune route ; une question inactive reste référencée par les historiques.
- Sélection : uniquement questions actives visibles dans le tenant courant.
- Catalogue système réel : non inventé et non seedé ; un corpus allemand validé doit être importé avant utilisation réelle.

## 4. Test de placement

- Sélection V1 déterministe : trois questions actives par niveau, soit 18 au total ; démarrage refusé si un niveau est incomplet.
- Une seule tentative `started` par apprenant et tenant, défendue par transaction et index unique partiel.
- Reprise : autorisée sans durée maximale V1 ; les réponses déjà validées sont verrouillées.
- Snapshot : énoncé, quatre choix, bonne réponse et niveau copiés au démarrage.
- Réponse : A–D, une écriture unique, correction calculée côté serveur ; la bonne réponse n’est jamais envoyée à la page de passage.
- Finalisation : refusée tant que les 18 réponses ne sont pas présentes ; une seconde finalisation est refusée.
- État : `started → completed → reviewed`. `completed` reste une suggestion ; `reviewed` est la décision humaine.

## 5. Scoring

### Exigence du cahier des charges

Le serveur doit calculer le score, proposer un niveau A1–C2 et conserver une règle reproductible. Le cahier ne donne pas de seuils officiels.

### Décision technique EduXora

Algorithme : `pourcentage = bonnes réponses / 18 × 100`, arrondi à deux décimales, puis sélection du seuil inférieur atteint.

| Minimum | Niveau |
|---:|:---|
| 0 % | A1 |
| 35 % | A2 |
| 50 % | B1 |
| 65 % | B2 |
| 80 % | C1 |
| 90 % | C2 |

- Version : `v1`.
- Origine : décision technique EduXora centralisée dans `config/placement.php`, documentée par ADR-017 et couverte à toutes les bornes.
- Portée : seuils provisoires et réversibles ; ils ne constituent pas une certification linguistique officielle.

## 6. Placement

Le niveau suggéré vient exclusivement du score serveur. Le groupe suggéré doit être actif, allemand, du même niveau et disposer d’une place. Le choix favorise l’effectif le plus faible puis le nom ; aucun groupe disponible produit `null`.

La direction valide une seule fois le niveau et un groupe facultatif. Suggestions et décisions sont conservées séparément. Un override exige un motif d’au moins dix caractères. La capacité est revérifiée sous verrou. Si un autre groupe actif existe, le transfert administratif doit être explicite avant validation. Deux décisions concurrentes ne peuvent pas consommer la même dernière place.

## 7. Progression

- `learners.current_level` est initialisé depuis `initial_level`.
- Une évolution réelle crée `learner_level_histories` ; aucune ligne n’est modifiable ou supprimable par Eloquent.
- L’enseignant peut changer le niveau avec motif uniquement pour un apprenant affecté activement à l’un de ses groupes.
- La direction agit sur tout le tenant.
- Les sources sont `placement_test`, `teacher_evaluation` et `director_override`.
- Chaque changement produit `learner.level_changed` dans l’audit.

## 8. Architecture livrée

### Migration

- `database/migrations/2026_08_21_000500_create_pedagogy_tables.php`

### Modèles

- `PlacementQuestion`
- `PlacementAttempt`
- `PlacementAttemptQuestion`
- `LearnerLevelHistory`
- extension de `Learner` avec niveau courant, tentatives et historique

### Enums

- `QuestionSource`
- `QuestionStatus`
- `PlacementAttemptStatus`
- `LevelChangeSource`
- réutilisation de `LearnerLanguage` et `LearnerLevel`

### Actions, Queries et Service

- `CreatePlacementQuestion`, `UpdatePlacementQuestion`, `DisablePlacementQuestion`, `EnablePlacementQuestion`
- `StartPlacementAttempt`, `RecordPlacementAnswer`, `CompletePlacementAttempt`, `ReviewPlacementAttempt`
- `ChangeLearnerLevel`
- `VisibleQuestionQuery`, `PlacementQuestionSelector`, `GroupSuggestionQuery`
- `PlacementScorer`

### HTTP et autorisation

- contrôleurs `PlacementQuestionController`, `PlacementTestController`, `LearnerPedagogyController`
- requests `IndexPlacementQuestionRequest`, `StorePlacementQuestionRequest`, `UpdatePlacementQuestionRequest`, `RecordPlacementAnswerRequest`, `ReviewPlacementAttemptRequest`, `UpdateLearnerLevelRequest`
- policies `PlacementQuestionPolicy`, `PlacementAttemptPolicy` et extension `LearnerPolicy`
- 14 routes tenantées : banque (7), test (5), suivi apprenant et niveau (2)

### Vue

- `Components/Pedagogy/QuestionForm.vue`
- `Pages/Pedagogy/Questions/{Index,Create,Edit}.vue`
- `Pages/Pedagogy/Placement/{Take,Result}.vue`
- `Pages/Pedagogy/LearnerHistory.vue`
- navigation et fiche apprenant enrichies ; les membres d’un groupe lient vers leur suivi.
- `vite.config.js` autorise explicitement l’origine locale `APP_URL` pour charger les modules Vite depuis le port Docker exposé.

## 9. Base de données

Tables créées : `placement_questions`, `placement_attempts`, `placement_attempt_questions`, `learner_level_histories`. Colonne ajoutée : `learners.current_level` non nullable.

Les tables d’exécution portent `organization_id` et `BelongsToTenant`. Les FK composites protègent les liens vers apprenant, tentative et groupe. Les questions globales utilisent une FK simple et ne sont jamais supprimées. Les index couvrent visibilité de banque, statut/niveau, historique apprenant, recherches de tentative et unicité d’une tentative active.

Les checks SQL couvrent source/propriété, langue, niveaux, A–D, forme JSON, activation/désactivation, transitions de tentative, cohérence score/timestamps, cohérence réponse/timestamps, niveau réellement modifié et lien histoire/tentative. Les snapshots empêchent une édition de question d’altérer un test démarré. Transactions et verrous protègent les mutations et la capacité.

## 10. Sécurité vérifiée

- IDOR et cross-tenant : question, tentative, apprenant et groupe étrangers retournent 404 ou 403 sans fuite.
- RBAC : direction complète ; secrétariat passage sans banque mutable ni revue ; enseignant limité à ses groupes ; comptable refusé.
- Questions système : modification et désactivation refusées.
- Manipulation de score/niveau : champs client supplémentaires ignorés ; score et suggestions restent serveur.
- Injection groupe/apprenant : résolution sous scope tenant et tests A/B explicites.
- XSS : rendus Vue par interpolation ; test Vitest vérifiant qu’un énoncé `<script>` reste du texte sans élément script.
- Tentative terminée : réponse, seconde finalisation et seconde revue refusées.
- Audit : aucun secret/réponse correcte n’est journalisé ; scan local de motifs sensibles sans résultat Phase 6.

## 11. Tests et qualité

```text
Laravel : 76 tests / 523 assertions — PASS
Tests Phase 6 : 17 tests / 92 assertions — PASS

Vitest : 7 fichiers / 34 tests — PASS
Vitest Phase 6 : 6 tests — PASS

Composer validate : PASS
Pint : PASS — 214 fichiers
Larastan : PASS — 0 erreur / 163 fichiers
ESLint : PASS — 0 warning
Prettier : PASS
vue-tsc : PASS
Vite : PASS — 2693 modules transformés
Composer audit : PASS — aucune vulnérabilité connue
npm audit : PASS — 0 vulnérabilité production

migrate:fresh : PASS — base dédiée eduxora_testing
rollback : PASS — base dédiée eduxora_testing
migrate : PASS — base dédiée eduxora_testing

PostgreSQL : PASS
Redis : PASS
Docker : PASS — services de référence healthy, node running

git diff --check : PASS
```

## 12. Responsive

```text
375 px : NOT TESTED
390 px : NOT TESTED
768 px : NOT TESTED
1024 px : NOT TESTED
1280 px : NOT TESTED
1440 px : NOT TESTED
Safari iOS 13 : NOT TESTED
```

Le skill navigateur local a été chargé, mais son runtime obligatoire `mcp__node_repl__js` n’était pas exposé. La structure responsive, l’accessibilité des labels/états, Vitest, ESLint, Prettier, TypeScript et le build ont été vérifiés ; cela ne remplace pas un test visuel réel.

## 13. Limites constatées

- Aucun corpus système allemand validé n’est fourni ; un environnement neuf refuse correctement de démarrer tant que trois questions actives par niveau ne sont pas importées.
- Les seuils `v1` sont techniques et doivent recevoir une validation pédagogique avant production.
- La V1 ne comporte ni chronomètre, ni randomisation, ni antifraude avancée.
- Un changement manuel de niveau ne déplace pas silencieusement l’apprenant vers un autre groupe.
- Le navigateur réel, Safari iOS 13 et la CI distante ne sont pas testés.
- Lors du premier contrôle, `php artisan migrate:fresh --env=testing` a utilisé la base Docker locale `eduxora`, car `--env` ne charge pas les variables de `phpunit.xml`. Son schéma a été recréé, mais d’éventuelles données locales antérieures ont été supprimées. Tous les cycles suivants ont injecté explicitement `DB_DATABASE=eduxora_testing`. Aucune base distante n’a été touchée.

## 14. Git final

```text
HEAD : 1b794599aa5c4173bdddd7272ec12b98abd715ce
Working tree : MODIFIÉ — changements Phase 6 non commités

Tracked modified : 21
Untracked : 47
Staged : 0

stash@{0} : conservé — wip: phase 4 groups and scheduling preserved after v0.3.0
stash@{1} : conservé — checkpoint: phase 4 isolated files
stash@{2} : conservé — checkpoint: full pre-split phases 2-4

Commit Phase 6 : NON
Tag Phase 6 : NON
Push : NON
Phase 7 : NON commencée
```
