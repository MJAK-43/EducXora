# ADR-010 — L’organisation comme frontière de tenant

- Statut : Accepted
- Date : 2026-08-18
- Remplace partiellement : ADR-002 (`center_id`)

## Contexte

La Phase 2 nomme explicitement le tenant `Organization` et prévoit qu’une identité puisse appartenir à plusieurs organisations. Le terme `center_id` de l’ADR-002 ne couvre pas correctement cette frontière SaaS et risquerait de dupliquer l’identité utilisateur.

## Décision

`organizations` est la source de vérité du tenant et `organization_user` porte les adhésions. Toute donnée métier tenantée porte un `organization_id` non fourni par le client. `TenantContext`, un scope Eloquent fail-closed, des policies et des clés étrangères composites forment la défense en profondeur. La sélection du tenant vient uniquement d’une adhésion active stockée en session ; une organisation suspendue bloque les routes tenantées.

Les opérations plateforme utilisent des routes et un middleware Super Admin séparés. PostgreSQL RLS reste différée tant qu’un protocole fiable de contexte par transaction/connexion et pour les workers de queue n’est pas démontré ; activer une RLS contournable donnerait une fausse garantie.

## Conséquences

Une identité peut changer d’organisation sans duplication. Les relations rôle/adhésion ne peuvent pas traverser un tenant grâce aux FK composites. Chaque nouveau modèle tenanté doit utiliser le concern `BelongsToTenant` et recevoir un test A/B. Les lectures globales exigent un chemin plateforme explicite et audité.

## Alternatives écartées

Un utilisateur par organisation (doublons d’identité), une base par tenant (coût opérationnel prématuré), des filtres uniquement dans les contrôleurs (fragiles), ou une RLS activée sans gestion sûre des connexions persistantes.

## Critères de révision

Revoir lors de l’introduction des jobs tenantés ou si le modèle devient multi-base. La RLS ne pourra être activée qu’avec tests web/queue/transactions prouvant l’absence de réutilisation de contexte.
