# ADR-014 — Administration tenant-scoped des apprenants

- Statut : Accepted
- Date : 2026-08-21

## Contexte

La demande explicite de Phase 3 introduit uniquement l’administration de base des apprenants. L’ancien backlog associait ce domaine à une Phase 4 avec les groupes ; cette demande plus récente fait autorité et exclut formellement les groupes, le planning, les présences, la pédagogie et la finance.

Les dossiers contiennent des données personnelles et une photo facultative. Ils doivent rester strictement isolés par organisation, consultables après autorisation backend et archivables sans suppression destructive.

## Décision

- `Learner` vit dans `app/Domain/Learner` et porte un `organization_id` obligatoire protégé par le tenant context et son scope fail-closed.
- Un UUIDv7 est exposé ; l’identifiant interne n’est pas utilisé dans les URL.
- Le cycle de vie est `active` ou `archived`. L’archivage conserve le dossier, l’acteur et l’horodatage ; aucun soft-delete ni suppression métier n’est introduit.
- Les mutations passent par des Form Requests, une policy et les Actions `CreateLearner`, `UpdateLearner`, `ArchiveLearner` et `RestoreLearner`.
- Le téléphone est normalisé au format `+237XXXXXXXXX` et n’est pas unique.
- Les photos JPEG/PNG/WebP sont limitées à 2 Mo et 4096 px, enregistrées sur le disque privé `local`, puis servies par un contrôleur autorisé avec cache privé.
- Les exports utilisent un CSV UTF-8 compatible tableur, filtré par le tenant context et limité par rate limiting. Aucun paquet XLSX/PDF n’est ajouté au socle.
- Les événements `learner.created`, `learner.updated`, `learner.archived` et `learner.restored` sont consignés dans l’audit append-only avec des métadonnées minimales.

## Conséquences

Le domaine est autonome et prêt à recevoir des relations lors des phases futures, sans les anticiper. Les accès inter-organisations échouent avant la policy grâce au scope tenant, puis la policy reste une seconde barrière. Les photos ne possèdent aucune URL publique prévisible.

Les formats XLSX et PDF natifs restent une dette fonctionnelle conditionnée à l’adoption explicite de dépendances maintenues. Le CSV actuel couvre l’export opérationnel sans élargir la surface de dépendances.

## Critères de révision

Réviser cette décision si les règles de consentement photo des mineurs sont précisées, si une politique de rétention/anonymisation est adoptée ou si un format XLSX/PDF natif devient une exigence ferme.
