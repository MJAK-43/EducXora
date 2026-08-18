# PHASE 0 — RAPPORT

Date de validation : 18 août 2026

## Repository analysé

- Dossier local `C:\wamp64\www\inovixora\educXora` inspecté intégralement avant modification.
- État initial : un seul fichier, `EduXora_CahierDesCharges.pdf` (287 746 octets) ; aucune métadonnée `.git`, aucun code, dépendance, configuration ou consigne agent.
- Repository déclaré `https://github.com/MJAK-43/EducXora.git` vérifié en lecture seule : dépôt accessible mais aucune référence Git publiée (`git ls-remote` vide), cohérent avec un repository vide.
- Aucun scaffold Laravel/Vue et aucune fonctionnalité métier créés en Phase 0.

## Documents analysés

- `EduXora_CahierDesCharges.pdf`, version 1.0, InoviXora 2025 : 18/18 pages extraites et lues, non chiffrées, toutes porteuses de texte.
- SHA-256 : `7E0FFF6EDCBE0826380925209E5F5E9D103892E1B1051210DBC5E107272C4456`.
- Mission de cadrage reçue le 18/08/2026, analysée comme extension explicite du référentiel : SaaS, Super Admin, RBAC, audit, sécurité, offline, billing et phases renforcées.
- Documentation officielle Laravel 13 et Tailwind CSS consultée pour confirmer PHP 8.3 minimum et l'incompatibilité Tailwind 4/iOS 13 (Safari 16.4+ requis).

## Architecture proposée

- Monolithe modulaire Laravel 13, domaines métier séparés, contrôleurs minces, Actions/Queries/Policies/Events/Jobs.
- Vue 3 + Composition API + TypeScript strict + Inertia/Vite ; endpoints JSON ciblés pour offline, webhooks et futur mobile.
- PostgreSQL partagé, schéma partagé, `center_id` systématique sur données tenant, TenantContext fail-closed, scopes/policies, FK composites et RLS en défense supplémentaire après validation.
- Redis pour sessions, cache, verrous et queues ; stockage objet privé pour documents.
- Domaines : Center, Identity, Student, Learning, Scheduling, Attendance, Assessment, Finance, Payment, Communication, Reporting, Subscription et Audit.
- Développement Docker Compose ; images immuables et promotion par digest envisagées pour recette/production.

## Choix techniques

- PHP 8.4 conteneurisé pour Laravel 13 (`>=8.3`) ; le PHP local 8.2.18 ne suffit pas.
- UUIDv7, temps UTC/fuseau centre, XAF stocké en entier, règles pédagogiques versionnées.
- Design system CSS/PostCSS/Autoprefixer ciblant iOS 13 ; Tailwind CSS 4 écarté.
- Providers/adapters pour Mobile Money et notifications ; fakes obligatoires.
- Jobs asynchrones idempotents pour fournisseurs, messages, PDF et exports.
- Tests unitaires, feature, intégration, composants et E2E ; PostgreSQL réel pour les invariants DB.

## Décisions importantes

Neuf ADR consignent : monolithe modulaire, multi-tenancy, identité/RBAC, frontend/CSS, finance/paiements, offline, intégrations asynchrones, conventions de données/audit et déploiement. L'ADR déploiement reste provisoire faute de cible d'hébergement.

Les factures, échéanciers, tentatives, transactions, paiements, allocations, remboursements et reçus sont séparés. Un échec Mobile Money ferme la tentative en échec mais laisse la dette ouverte ; aucun reçu n'est produit. Le pointage offline utilise des commandes UUID + version de base ; le serveur déduplique et expose les conflits sans last-write-wins silencieux.

## Risques

- jalon historique de trois mois non estimable sans équipe/capacité ;
- délais contractuels et techniques MTN, Orange, WhatsApp/SMS ;
- conformité camerounaise finance, fiscalité, données et mineurs non expertisée ;
- complexité iOS 13/PWA et synchronisation concurrente ;
- règles métier pédagogiques/financières insuffisamment définies ;
- accès Super Admin, exports et finance comme surfaces majeures de fuite/fraude ;
- SLO 99 %, absence de perte, RPO/RTO et hébergement non contractualisés.

## Questions métier restantes

25 questions sont répertoriées dans `docs/requirements.md`, avec hypothèse réversible et phase limite. Les plus structurantes concernent : périmètre allemand/multilingue, PWA versus application native/hybride, correction des présences, scoring A1–C2, coefficients, validation de niveau, échéanciers/statuts d'impayé, allocation/surpaiement, remboursement, numérotation légale, fournisseurs Mobile Money, consentements/communications, mineurs, rétention, RPO/RTO et suspension SaaS.

## Fichiers créés/modifiés

- racine : `.gitignore`, `README.md`, `AGENTS.md` ;
- architecture et qualité : `docs/architecture.md`, `database.md`, `security.md`, `testing.md`, `design-system.md`, `development.md` ;
- stratégies : `docs/offline.md`, `payments.md`, `requirements.md` ;
- phases : `docs/phases/backlog.md`, présent rapport ;
- décisions : index et ADR-001 à ADR-009 sous `docs/decisions/`.

Le PDF source est resté inchangé. L'outil d'extraction temporaire a été supprimé après lecture.

## Tests/vérifications

- présence des 15 livrables documentaires requis avant rapport : PASS, 0 manquant ;
- lecture PDF : PASS, 18/18 pages, empreinte calculée ;
- encodage UTF-8 strict des 23 fichiers Markdown : PASS, 0 erreur ;
- scan marqueurs critiques/secrets usuels : PASS, aucun résultat ;
- liens Markdown après création du rapport : PASS, 0 lien cassé ;
- structure Markdown : PASS, 0 fichier sans titre H1 et 0 espace final ;
- présence du paquet temporaire : PASS, supprimé ;
- vérification distante GitHub : PASS, aucune référence publiée ;
- tests backend/frontend, linters, TypeScript, migrations, build et responsive runtime : non applicables, car la Phase 0 interdit de générer l'application.

## Résultat

**PASS.** Les questions ouvertes ne bloquent pas la clôture d'architecture Phase 0 ; elles bloquent les phases indiquées si elles restent sans décision.

## PHASE 0 — TERMINÉE ET VALIDÉE

Ne pas commencer la Phase 1 sans autorisation explicite.
