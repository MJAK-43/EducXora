# Backlog directeur par phases

> Numérotation faisant autorité au 2026-08-21 : le cadrage validé par le commanditaire désigne le suivi des présences comme **Phase 5**. Il remplace les anciens libellés historiques « Phase 5 — Planning » et « Phase 6 — Présences » plus bas. La Phase 4 contient déjà Groupes et Planning ; la Phase 5 Attendance est implémentée sans démarrer la Phase 6.

Ce backlog traduit le cahier des charges et la mission renforcée. Chaque phase commence par raffinement, matrice de traçabilité et risques, puis se termine par son rapport. Aucun démarrage automatique de la phase suivante.

## Phase 0 — Audit, cadrage et architecture

**Livrables :** inventaire, analyse du PDF, architecture, modèle macro, stratégies tenant/RBAC/sécurité/offline/paiement/frontend/tests/CI, ADR, questions, backlog et consignes agents.

**Acceptation :** documentation cohérente et reliée, aucune fonctionnalité métier générée, vérifications documentaires passées.

## Phase 1 — Foundation

- Laravel 13/PHP 8.4, Vue 3/TS strict/Inertia/Vite ;
- Docker Compose Nginx/PHP/PostgreSQL/Redis/workers/scheduler/Mailpit ;
- structure modulaire, primitives Support, design tokens/composants de base/AppShell ;
- Pint, Larastan/PHPStan, ESLint, Prettier, vue-tsc, Vitest, tests Laravel ;
- CI PR, `.env.example`, healthchecks, logs structurés, scripts QA ;
- preuve responsive/iOS 13 du socle CSS.

**Gate :** installation reproductible, migrations de base/rollback, tous outils verts, aucun secret, baseline performance/accessibilité.

## Phase 2 — Authentication & Multi-Tenancy

- centers, users, memberships, rôles/permissions et seeds de rôles ;
- login email/téléphone, logout, reset, sessions, audit d'auth ;
- TenantContext, scope/policies, FK composites et RLS évaluée/activée ;
- Super Admin séparé, suspension, rate limits, sécurité cookie ;
- tests Centre A/B incluant HTTP, jobs, cache et fichiers.

**Gate :** matrice d'isolation obligatoire verte et threat model mis à jour.

## Phase 3 — Administration des centres

- profil, paramètres, branding/cachet privé, utilisateurs, rôles et permissions ;
- administration plateforme centres/activation/suspension ;
- UX invitations et états compte ; accès support seulement si Q-023 tranchée.

## Phase 4 — Apprenants & Groupes

**Statut au 2026-08-21 : terminée et validée selon le cadrage explicite révisé « Groupes/Classes & Planning ». La partie apprenants avait été livrée en Phase 3 ; le planning initialement numéroté Phase 5 a été absorbé dans cette Phase 4.**

- apprenants, inscriptions, archivage, recherche, langues/niveaux ;
- groupes, capacités, affectations historisées ;
- DataTable responsive, import contrôlé, exports autorisés ;
- données mineurs/tuteurs selon Q-019.

## Phase 5 — Planning

**Statut : périmètre historique absorbé par la Phase 4 révisée ; aucun développement additionnel n’est ouvert sous ce numéro.**

- salles, séances, vues groupe/enseignant, création/modification/annulation/archive ;
- interdiction de chevauchement enseignant/salle/groupe en app et DB ;
- événements d'annulation prêts pour Communication.

## Phase 6 — Présences

- feuille DRAFT/VALIDATED/CORRECTED, apprenants et enseignant ;
- permissions d'affectation, corrections auditées, historiques et statistiques ;
- exports, séances non pointées et tests de concurrence ;
- contrats/API de sync préparés, sans PWA offline complète.

## Phase 7 — Finance Core

- catalogue frais, factures/lignes, échéanciers/échéances ;
- cash, paiements partiels, allocations, soldes/statuts, reçus ;
- remboursements, impayés, rapports de caisse/créances ;
- invariants transactionnels, concurrence, numérotation et audit.

**Gate :** Q-011, Q-013, Q-014 et Q-015 tranchées ; revue finance/sécurité.

## Phase 8 — Mobile Money

- contrats providers, fakes, MTN, Orange, intents/attempts/transactions ;
- webhooks vérifiés/idempotents, polling, reconciliation et observabilité ;
- erreurs, délais, retries, refunds provider ;
- aucun reçu avant succès confirmé.

**Gate :** documentation officielle/sandbox disponible, tests doublons/désordre/concurrence verts.

## Phase 9 — Pédagogie

- banque de questions système/centre, blueprints et test allemand ;
- sessions/réponses/résultats, stratégie versionnée de placement et validation Directeur ;
- progression, évaluations/composants, notes, moyennes, bulletins PDF ;
- questions officielles non éditables et historiques reproductibles.

## Phase 10 — Communications

- contrats SMS/WhatsApp/email/in-app, fakes et providers retenus ;
- templates versionnés, variables autorisées, consentements/préférences ;
- queues, relances, livraison, retries/dead letters, historique expurgé ;
- envoi manuel apprenant/groupe et événements annulation/paiement/bulletin.

## Phase 11 — Dashboard & Reporting

- KPI Directeur définis et sourcés, vues par rôle ;
- tendances utiles, alertes, dernières opérations ;
- exports PDF/XLSX asynchrones, privés, tenant-scoped ;
- budgets de requêtes et absence de N+1.

## Phase 12 — Offline / PWA

- manifest, Service Worker, IndexedDB et cache minimal ;
- file de commandes, sync idempotente, conflits/résolution et indicateurs ;
- compatibilité de schéma et purge ;
- tests réseau/appareil ; décision wrapper natif (Q-002).

## Phase 13 — SaaS Billing

- plans/features, subscriptions/périodes/essais, activation/expiration/suspension ;
- quotas retenus, facturation SaaS séparée, notifications et Super Admin ;
- politique d'accès en suspension et préparation provider indépendante.

## Phase 14 — Hardening

- audit OWASP/tenant/finance, dépendances/images/secrets ;
- charge/performance/indexes/cache/queues ;
- WCAG, responsive/navigateurs/appareils ;
- backups/restauration, RPO/RTO, logs/metrics/traces/alertes ;
- runbooks incident, rotation, reprise et migrations.

## Phase 15 — Bêta

- onboarding et données de démonstration pour trois centres pilotes ;
- E2E complet MUST, monitoring, sauvegardes, support et rollback ;
- documentation utilisateur/administrateur et formation ;
- collecte structurée des retours, triage, corrections sans bug bloquant.

## Jalons et dépendances externes

La cible historique Mois 1/2/3 est un objectif produit, pas une estimation validée : taille d'équipe, budget, contractualisation provider et disponibilité pilotes manquent. Les démarches MTN/Orange/WhatsApp, conformité locale et définition finance doivent être lancées en parallèle du développement, sans implémenter une API non documentée.
