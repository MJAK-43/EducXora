# ADR-012 — Invitations temporaires et non rejouables

- Statut : Accepted
- Date : 2026-08-18

## Contexte

Une invitation donne accès à un tenant et à un rôle. Un jeton en clair en base, sans expiration ou réutilisable, créerait une capacité d’accès durable et difficile à révoquer.

## Décision

Le jeton aléatoire de 64 caractères n’est envoyé qu’au destinataire ; seule son empreinte SHA-256 est stockée. Une invitation est liée à une organisation, un e-mail, un rôle du même tenant, un invitant, une expiration de 72 heures et un horodatage d’acceptation. Elle est à usage unique.

Un compte existant doit être authentifié avec l’adresse invitée. Un nouveau compte créé par acceptation est considéré comme e-mail vérifié, car la possession du lien prouve le contrôle de l’adresse. L’acceptation, l’adhésion et l’attribution de rôle sont transactionnelles et auditées.

## Conséquences

Une fuite de base ne révèle pas de jeton utilisable. La révocation consiste à supprimer/remplacer l’invitation active. Les e-mails et logs ne doivent jamais contenir l’empreinte ou le jeton.

## Alternatives écartées

Jeton en clair, invitation sans expiration, acceptation par une adresse différente, ou rôle global porté par l’utilisateur.

## Critères de révision

Revoir la durée et la rotation lorsqu’un fournisseur d’e-mail transactionnel et des métriques de délivrabilité seront disponibles.
