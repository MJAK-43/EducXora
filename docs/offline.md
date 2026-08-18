# Stratégie offline — présences

## Périmètre

Le mode offline V1 couvre uniquement la consultation des séances préchargées et la saisie/validation de présence. Aucun paiement, remboursement, changement de rôle, export ou modification d'identité n'est autorisé hors connexion.

## Modèle local

Le Service Worker met en cache le shell versionné et les assets statiques. IndexedDB contient :

- snapshots minimaux des séances autorisées et listes d'apprenants ;
- brouillons de pointage ;
- file de commandes sortantes ;
- accusés/résultats de synchronisation ;
- métadonnées de version et d'expiration.

Chaque snapshot porte `center_id`, `session_id`, version serveur, échéance et empreinte. Les données sont purgées à la déconnexion, au changement d'utilisateur/centre, à l'expiration ou lors d'une révocation détectée. Les photos et données financières ne sont pas cachées.

## Commande synchronisable

```text
client_command_id : UUIDv7 unique généré sur l'appareil
center_id         : informatif, contrôlé contre la session
device_id         : pseudonyme local rotatif
session_id
base_version
command_type      : SAVE_DRAFT | VALIDATE_SHEET
payload           : statuts apprenants + présence enseignant
client_recorded_at
```

Le backend calcule le tenant depuis l'identité, vérifie affectation/permission et stocke la clé idempotente avant effet. Une même commande rejouée retourne le résultat original.

## Cycle de synchronisation

```text
LOCAL_DRAFT → QUEUED → SYNCING → SYNCED
                          ├────→ CONFLICT
                          └────→ RETRYABLE_ERROR / REJECTED
```

La connectivité navigateur est un indice, jamais une preuve : une requête de santé confirme la reprise. Les commandes sont envoyées par lots bornés, dans l'ordre logique par séance, avec backoff et jitter. L'interface distingue « enregistré sur cet appareil » de « confirmé par le serveur ».

## Conflits

Un `base_version` différent, une séance annulée, une feuille validée ailleurs, une adhésion suspendue ou une affectation modifiée produit un conflit/rejet explicite. Le serveur renvoie snapshot actuel et différences. Aucun last-write-wins silencieux.

Résolution proposée :

1. conserver la proposition locale ;
2. afficher valeur locale et serveur champ par champ ;
3. autoriser Directeur, et Secrétaire si la règle est confirmée, à choisir/justifier ;
4. créer une correction auditée ;
5. acquitter puis purger la commande locale.

## Sécurité

IndexedDB ne constitue pas un coffre sécurisé. Réduire contenu et durée, interdire postes partagés non verrouillés dans la politique d'usage, utiliser HTTPS, protéger contre XSS avec CSP et dépendances contrôlées. Les tokens restent en cookie HttpOnly ; aucune clé API ni session persistante dans IndexedDB/localStorage.

## Versionnement et déploiement

Le Service Worker n'active une nouvelle version qu'après compatibilité du schéma local. Une migration IndexedDB échouée garde l'ancienne file exportable/récupérable et bloque la saisie plutôt que de perdre des commandes. Les APIs de sync sont versionnées et supportent au moins la version cliente précédente pendant une fenêtre définie.

## Validation

Tester mode avion, réseau instable, rechargement/fermeture, multi-onglets, quota, doublons, ordre inversé, conflit, session expirée, permissions révoquées et mise à jour de Service Worker. La Phase 12 doit inclure tests sur appareils iOS 13/Android 8 réels ou représentatifs.
