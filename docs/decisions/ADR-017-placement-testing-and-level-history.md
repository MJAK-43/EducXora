# ADR-017 — Test de positionnement et historique de niveau

- Statut : Accepted
- Date : 2026-08-22
- Phase : 6 — Suivi pédagogique & Test de niveau

## Contexte

EduXora doit proposer un test de positionnement allemand A1–C2 sans confondre une suggestion calculée avec une décision pédagogique. Les questions système doivent être partagées et verrouillées, tandis qu’une organisation peut enrichir sa propre banque sans exposer son contenu à un autre tenant. Une modification ultérieure d’une question ou d’un barème ne doit jamais réécrire le sens d’un résultat historique.

Le cahier des charges ne fournit ni seuils officiels, ni composition exacte du test. Il exige toutefois 15 à 30 questions, une reprise possible, un calcul serveur, une suggestion de groupe et une validation humaine.

## Décision

### Banque et propriété

`placement_questions` contient deux sources :

- `system`, avec `organization_id = NULL`, visible par tous les tenants et en lecture seule dans les parcours métier ;
- `organization`, avec `organization_id` obligatoire, visible et modifiable uniquement dans le tenant propriétaire.

Une question porte la langue allemande `de`, un niveau CECRL, un énoncé, exactement quatre choix A–D, une bonne réponse et un statut actif/inactif. Aucune suppression fonctionnelle n’est exposée. Le catalogue système n’est pas rempli de contenu pédagogique inventé : son import devra provenir d’un corpus validé.

### Blueprint V1 et instantané

Le blueprint `v1` sélectionne 18 questions actives, soit trois par niveau A1–C2, dans un ordre déterministe. Le démarrage échoue explicitement si un niveau ne possède pas les trois questions requises. Une seule tentative `started` est permise par apprenant et tenant ; elle peut être reprise sans durée maximale en V1.

Chaque `placement_attempt_question` photographie l’énoncé, les quatre choix, la bonne réponse et le niveau. La réponse est écrite une seule fois. La bonne réponse de l’instantané n’est jamais envoyée à l’écran de passage.

### Score et décision humaine

Le serveur calcule `raw_score` et le pourcentage. Le barème `v1` est centralisé dans `config/placement.php` : A1 à partir de 0 %, A2 à 35 %, B1 à 50 %, B2 à 65 %, C1 à 80 % et C2 à 90 %. Ces seuils sont une configuration technique V1 réversible, pas une certification linguistique officielle. Toute nouvelle règle exige une nouvelle version afin de reproduire les résultats historiques.

Le groupe suggéré est le groupe actif allemand du niveau obtenu qui possède une capacité disponible, trié par effectif puis nom. Une absence de groupe disponible produit une suggestion nulle, jamais une affectation forcée.

La finalisation place la tentative en `completed` et conserve seulement une suggestion. Un Directeur/Organization Admin ou Manager autorisé effectue ensuite une revue unique : il valide ou remplace le niveau et le groupe. Une divergence exige un motif d’au moins dix caractères. Suggestions et valeurs validées restent stockées séparément. Une affectation concurrente verrouille le groupe et revérifie sa capacité ; un apprenant déjà affecté à un autre groupe doit d’abord passer par le retrait administratif existant.

### Niveau courant et historique

`learners.initial_level` reste immuable comme valeur d’entrée historique ; `learners.current_level` porte le niveau opérationnel. Chaque changement réel produit une ligne append-only dans `learner_level_histories` avec ancienne/nouvelle valeur, source, acteur, motif, tentative éventuelle et instant.

Un enseignant peut modifier le niveau courant après évaluation manuelle uniquement pour un apprenant affecté activement à l’un de ses groupes. La direction possède le périmètre tenant complet. Le secrétariat peut démarrer et faire passer un test, mais ne gère pas la banque et ne valide pas la décision finale. Le comptable n’a aucun accès Phase 6.

## Garanties techniques

- `organization_id` est imposé par `TenantContext` sur toutes les tentatives, réponses et histoires ;
- les FK composites bloquent les références inter-tenant ;
- une requête de banque explicite limite la visibilité aux questions système et au tenant courant ;
- transactions et `FOR UPDATE` protègent démarrage, réponse, finalisation, revue, niveau et capacité ;
- index unique partiel pour une seule tentative en cours par apprenant ;
- contraintes SQL sur sources, statuts, choix, transitions et cohérence des réponses ;
- audits `placement_question.*`, `placement_test.started/completed/reviewed` et `learner.level_changed` ;
- aucun score, niveau suggéré, tenant ou groupe interne n’est accepté comme vérité client.

## Conséquences

Les résultats restent explicables et reproductibles après modification de la banque. La décision humaine est traçable sans effacer la suggestion. La reprise est simple et sûre, mais la V1 n’offre ni chronomètre, ni randomisation cryptographique, ni mécanisme antifraude avancé. Le catalogue allemand validé doit être fourni avant qu’un environnement neuf puisse démarrer un vrai test.

## Alternatives écartées

- recalculer depuis la question courante : détruit la reproductibilité ;
- écrire directement le niveau calculé : confond score et décision humaine ;
- copier les questions système dans chaque tenant : multiplie les divergences ;
- utiliser `initial_level` comme niveau mutable : efface l’information d’entrée ;
- affecter automatiquement malgré un groupe complet ou existant : viole les invariants Phase 4.

## Critères de révision

Réviser cet ADR si un corpus officiel impose une autre distribution, si le produit décide d’une durée ou de plusieurs tentatives simultanées, si plusieurs langues sont activées, ou si une validation à deux étapes des évaluations enseignantes devient obligatoire.
