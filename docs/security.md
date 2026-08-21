# Stratégie de sécurité

## Contrôles livrés en Phase 1

- `.env` et variantes réelles sont ignorés ; seuls les exemples sans secret sont versionnables.
- Les réponses, y compris les erreurs non routées, reçoivent `nosniff`, `DENY`, Referrer-Policy et Permissions-Policy.
- En production, l'application ajoute une CSP restrictive ; HSTS est ajouté lorsque la requête est HTTPS.
- Les pages 403, 404 et 500 sont sobres et n'affichent aucune information technique. `APP_DEBUG=false` est obligatoire en production.
- `/dev/ui` et la commande de diagnostic sont refusés hors `local`/`testing`.
- Les sessions utilisent Redis, `HttpOnly`, `SameSite=Lax` et une inactivité de 30 minutes ; `SESSION_SECURE_COOKIE=true` doit être injecté en production HTTPS.
- PostgreSQL et Redis restent uniquement sur le réseau Docker interne. Mailpit ne relaie aucun email externe.
- Composer et NPM sont verrouillés ; les audits de dépendances font partie des contrôles de Phase 1.

Ces contrôles constituent un baseline et non le hardening complet OWASP. L'authentification, le RBAC et le multi-tenant restent explicitement hors Phase 1.

## Contrôles livrés en Phase 2

- identité globale UUIDv7, e-mail vérifié, état actif/inactif/suspendu/en attente et mots de passe hashés par Laravel ;
- rotation de session à la connexion, expiration absolue à 24 heures et expiration inactive à 30 minutes via Redis ;
- limitation des tentatives de connexion, inscription, reset et renvoi de vérification ; messages de reset/login non énumérants ;
- frontière tenant `Organization`, adhésion explicite, résolution serveur et refus fermé des modèles tenantés sans contexte ;
- organisation suspendue, adhésion inactive ou utilisateur inactif bloqués par middlewares indépendants ;
- RBAC d’adhésion avec permissions atomiques, Gates, policies et FK composites anti-cross-tenant ;
- Super Admin global séparé des adhésions et routes plateforme dédiées ;
- invitation expirante 72 h, token uniquement sous forme de hash et acceptation à usage unique ;
- confirmation de mot de passe sur les changements d’organisation/rôles et actions plateforme sensibles ;
- audit structuré append-only des événements d’identité, d’adhésion, de rôle, d’invitation et d’organisation, sans mot de passe/token.

MFA est préparée architecturalement mais non implémentée dans le périmètre Phase 2. Elle reste obligatoire avant ouverture publique de comptes Super Admin. PostgreSQL RLS est également différée jusqu’à preuve d’un contexte fiable sur connexions web et workers ; voir ADR-010 et ADR-011.

## Modèle de menace prioritaire

Les actifs critiques sont les données personnelles des apprenants, les documents, les écritures financières, les identités, les secrets fournisseurs et la séparation entre centres. Les menaces prioritaires sont : IDOR inter-tenant, élévation de privilèges, vol de session, fraude/rejeu de paiement, doublon de webhook, export massif, fuite par cache/fichier/job, injection, XSS, CSRF et abus de messagerie.

## Isolation multi-tenant

La requête authentifiée résout une adhésion active et construit un `TenantContext` serveur. Ce contexte :

- applique le scope tenant central aux modèles concernés ;
- remplit `center_id` à la création et rejette toute valeur client divergente ;
- alimente les policies, jobs, caches, verrous, fichiers et événements ;
- est absent des opérations plateforme globales, qui passent par un chemin Super Admin explicite et audité.

La base renforce l'isolation par FK composites. PostgreSQL RLS est ajoutée après tests du contexte de connexion. En cas d'absence de tenant dans une opération tenant-scoped, le comportement est **fail closed**.

## Authentification et sessions

- Laravel Fortify ou primitives Laravel équivalentes, sans logique maison de mot de passe/reset.
- Argon2id préféré si les ressources le permettent, sinon bcrypt avec coût mesuré.
- Identifiant email ou téléphone normalisé ; prévention de l'énumération de comptes.
- Cookies `Secure`, `HttpOnly`, `SameSite=Lax`, rotation de session à la connexion et après changement sensible.
- Expiration après 30 minutes d'inactivité et durée absolue maximale de 24 heures.
- Révocation des sessions à suspension, désactivation d'un centre, changement de mot de passe ou incident.
- MFA obligatoire pour Super Admin, fortement recommandé pour Directeur ; exigence finale à confirmer.
- Rate limits adaptatifs pour login, reset et MFA, avec journalisation sans secret.

## Protection des présences — Phase 5

- La séance, le groupe, l’enseignant et le roster sont dérivés côté serveur sous `TenantContext`; tous leurs identifiants de substitution sont interdits dans les payloads.
- Les FK composites et unicités PostgreSQL complètent le scope global fail-closed et `AttendanceSheetPolicy`.
- Un enseignant ne peut agir que sur les feuilles de sa propre adhésion ; les corrections exigent une permission administrative distincte.
- Une feuille validée est verrouillée au backend. Les corrections exigent un motif, conservent avant/après/auteur/date et produisent un audit minimal.
- Les tests tenant A/B couvrent consultation, démarrage, validation, correction et historique ; un apprenant hors roster est rejeté.

## RBAC et policies

Les permissions atomiques (`students.view`, `payments.refund`, etc.) sont accordées aux rôles d'une adhésion. Les rôles initiaux Directeur, Secrétaire/Caissière et Enseignant sont des modèles éditables dans les limites de séparation des tâches définies. Les policies contrôlent à la fois permission, tenant, état de ressource et relation métier (par exemple enseignant affecté au groupe).

Le Super Admin possède des permissions plateforme séparées. L'accès support temporaire à un centre, s'il est retenu, exige motif, durée courte, bannière d'impersonation, audit et impossibilité d'effectuer certaines opérations financières.

## Contrôles applicatifs

- Form Requests et DTO en liste blanche ; `$guarded` permissif interdit sur les modèles sensibles.
- Échappement Vue par défaut ; HTML riche désactivé ou assaini par bibliothèque dédiée.
- Requêtes paramétrées Eloquent/query builder ; aucun SQL concaténé avec entrée utilisateur.
- CSRF sur routes session ; CORS fermé par défaut.
- En-têtes : CSP progressive, HSTS en production, `frame-ancestors`, `nosniff`, Referrer-Policy et Permissions-Policy.
- Téléversements : MIME réel, extension, taille, dimensions, antivirus si disponible, nom serveur aléatoire, stockage privé.
- Téléchargements/exports : policy au moment de la demande **et** au téléchargement, expiration, signature, centre et utilisateur liés.

## Paiements et webhooks

- Secrets uniquement via coffre/configuration d'environnement, rotation prévue.
- TLS avec validation stricte ; aucun PIN Mobile Money n'entre dans EduXora.
- Signature, timestamp/fenêtre anti-rejeu, allowlist réseau seulement comme contrôle complémentaire.
- Corps brut conservé de façon expurgée/chiffrée selon nécessité, checksum et identifiant événement unique.
- Traitement idempotent sous transaction et verrou ; aucune génération de reçu avant succès confirmé.
- Réconciliation planifiée avec le fournisseur et alerte sur état incohérent.

## Données, logs et confidentialité

Chiffrement en transit partout et au repos pour volumes, backups et object storage. Les champs particulièrement sensibles sont chiffrés applicativement si l'analyse de données le justifie. Les logs sont structurés avec correlation ID, mais excluent mot de passe, token, secret, PIN, contenu de document, numéro de téléphone complet et payload brut non expurgé.

Les actions sensibles génèrent un audit append-only. L'audit n'est pas une sauvegarde et ne doit pas recopier toutes les données personnelles. Les droits d'accès aux logs, exports et backups sont séparés.

## Infrastructure et chaîne logicielle

- Conteneurs non-root, filesystem en lecture seule lorsque possible, images minimales et versionnées.
- PostgreSQL/Redis non exposés publiquement ; comptes et réseaux séparés par environnement.
- Secrets absents des images, du Git et des sorties CI.
- Dépendances verrouillées ; audit Composer/NPM, secret scanning, SAST et scan d'image dans CI.
- Sauvegardes chiffrées avec restaurations périodiques testées ; procédure incident et rotation des clés.

## Portes de validation

Chaque ressource tenant-scoped reçoit des tests d'accès croisé. Les opérations sensibles reçoivent tests d'autorisation, concurrence/idempotence et audit. Avant bêta : revue OWASP ASVS adaptée, test d'IDOR systématique, scan dépendances/images, vérification CSP/cookies/TLS, restauration backup et exercice de révocation de secret.

## Points restant à décider

Politique MFA Directeur, durée de conservation, consentement/tuteur pour mineurs, accès support, RPO/RTO, fournisseur de secrets, régions de stockage et exigences réglementaires camerounaises doivent être validés avant les phases concernées.

## Protection des dossiers apprenants

Le client ne peut fournir ni organization_id ni center_id. Le scope tenant, la policy Learner et les permissions atomiques forment trois barrières distinctes. Les contrôleurs résolvent les UUID après établissement du tenant context.

Les photos sont validées comme JPEG/PNG/WebP, limitées à 2 Mo et 4096 px, puis stockées sur le disque privé. Leur téléchargement exige learners.view, applique le scope tenant, renvoie Cache-Control: private et interdit le MIME sniffing.

L’export est rate-limité, filtré dans la requête tenant-scoped et ne reçoit aucun identifiant d’organisation client. Les tests couvrent explicitement lecture, écriture, archivage, restauration, photo et export entre deux organisations.

## Protection des groupes et du planning

- organization_id est interdit dans les Form Requests et attribué par le TenantContext.
- Les groupes, affectations et séances utilisent le scope tenant fail-closed ; les UUID sont résolus seulement après les middlewares tenant.
- Les clés étrangères composites empêchent les références groupe/apprenant/adhésion/séance entre organisations.
- L’enseignant est une adhésion active dotée du rôle Teacher/Trainer, pas un identifiant utilisateur libre fourni par le client.
- Les permissions group.* et schedule.* sont vérifiées par Gates et policies backend. L’enseignant ne voit que ses groupes et séances et ne peut pas les administrer.
- Les capacités et affectations sont contrôlées sous transaction et verrou ; un index partiel interdit les doubles affectations actives.
- Les conflits de planning sont validés par l’application puis garantis par des contraintes d’exclusion PostgreSQL résistantes à la concurrence.
- Archivage, retrait d’affectation et annulation conservent l’historique ; aucune route de suppression destructive n’est exposée.
- Les audits utilisent uniquement UUID, états et références minimales ; aucune notification ni donnée sensible n’est ajoutée aux logs.
