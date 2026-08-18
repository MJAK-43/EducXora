# ADR-002 — Multi-tenancy en schéma partagé

- Statut : Accepted
- Date : 2026-08-18

## Contexte

Chaque centre est un tenant et toute fuite inter-centre est critique. Une base par centre augmente fortement provisioning, migrations et reporting plateforme pour trois pilotes puis croissance SaaS.

## Décision

Utiliser PostgreSQL partagé et schéma partagé. Toute donnée tenant-scoped porte `center_id`. Un `TenantContext` serveur, scopes Eloquent, policies, attribution automatique, FK composites et tests Centre A/B forment la défense principale. PostgreSQL RLS sera activée comme défense supplémentaire après preuve fiable du contexte web/queue. Jobs, cache, fichiers, exports et broadcasts sont aussi segmentés.

## Conséquences

Opérations simples et coût maîtrisé, mais discipline absolue requise. Une requête sans tenant échoue fermée. Les opérations plateforme ont une voie distincte et auditée. Les FK composites alourdissent les migrations mais empêchent des relations cross-tenant silencieuses.

## Alternatives écartées

Conditions manuelles dans contrôleurs (fragiles) ; base par tenant (coût V1) ; RLS seule (contexte applicatif et tests restent nécessaires).
