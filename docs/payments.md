# Architecture finance et Mobile Money

## Deux sous-systèmes séparés

La finance apprenant (factures et encaissements d'un centre) et la facturation SaaS (abonnement du centre à EduXora) ne partagent ni facture, ni paiement, ni numérotation. Ils peuvent partager `Money`, idempotence, journalisation technique et contrats de provider.

## Concepts finance apprenant

- **Invoice** : créance et lignes facturées ; jamais preuve d'encaissement.
- **PaymentSchedule** : plan attendu et échéances ; ne crée pas de paiement.
- **PaymentAttempt** : tentative initiée par un opérateur/utilisateur.
- **PaymentTransaction** : échange et référence fournisseur, y compris callbacks.
- **Payment** : fonds confirmés (espèces validées ou fournisseur confirmé).
- **PaymentAllocation** : affectation d'un paiement à une facture/échéance.
- **Refund** : retour confirmé de tout ou partie des fonds avec motif.
- **Receipt** : document numéroté attestant un paiement confirmé.

Les soldes et statuts apprenant sont des projections de ces écritures, pas des badges saisis.

## Contrat provider

```text
PaymentProvider
  initiate(PaymentIntentData): ProviderInitiation
  fetchStatus(ProviderReference): ProviderStatus
  verifyWebhook(RawRequest): VerifiedWebhook
  requestRefund(RefundData): ProviderRefund

MtnMomoProvider
OrangeMoneyProvider
FakePaymentProvider
```

Le contrôleur appelle une Action métier qui persiste d'abord la tentative, commit, puis planifie l'appel provider. Timeouts courts, retries bornés, circuit breaker et correlation ID sont requis. Le fake permet succès, refus, timeout, doublon et callback désordonné.

## États

Tentative/transaction externe :

```text
PENDING → PROCESSING → SUCCESS
                    ├→ FAILED
                    └→ EXPIRED
SUCCESS → PARTIALLY_REFUNDED → REFUNDED
```

Une transition terminale n'est pas inversée par un callback plus ancien. Un échec marque la **tentative** `FAILED`; la dette reste ouverte/en attente de paiement. Cela résout l'ambiguïté du PDF « statut reste En attente » sans prétendre que la tentative a réussi.

## Flux de succès

1. Form Request valide montant, téléphone et permission.
2. L'Action verrouille la facture, vérifie solde et crée une tentative `PENDING` avec clé idempotente.
3. Après commit, un job appelle le provider et stocke référence externe/réponse expurgée.
4. Le webhook est authentifié et enregistré avec unicité `(provider, provider_event_id)`.
5. Un job verrouille tentative/facture, valide transition et montant/devise/référence.
6. Dans une même transaction : transaction `SUCCESS`, paiement confirmé, allocation, audit et événement outbox/after-commit.
7. Le reçu est numéroté et généré seulement après succès. La notification est un job distinct.

## Idempotence et réconciliation

- clé requête client unique par centre et intention ;
- référence provider unique dès disponibilité ;
- événement webhook unique avant traitement ;
- contrainte empêchant plusieurs effets de confirmation pour une tentative ;
- handlers idempotents retournant le résultat existant ;
- polling de secours et job de réconciliation périodique pour `PENDING/PROCESSING` anciens ;
- tableau d'écarts et action manuelle auditée, jamais correction SQL directe.

## Espèces

Une Action dédiée enregistre un paiement cash immédiatement confirmé par un rôle autorisé, sous transaction. Elle exige référence de caisse, auteur et date contrôlée. L'annulation n'efface pas : elle crée une opération compensatrice ou un remboursement selon la politique retenue.

## Remboursements

Motif obligatoire, permission renforcée et séparation initiateur/approbateur à confirmer selon montant. Le maximum remboursable est le paiement confirmé net des remboursements. Les callbacks de remboursement sont idempotents. Un reçu original demeure ; un document d'avoir/remboursement distinct est produit.

## Secrets et observabilité

Clés par environnement et provider, jamais au frontend. Logs structurés avec identifiants internes, provider, état, latence et code d'erreur normalisé, sans PIN/token/téléphone complet. Alertes sur taux d'échec, latence, callbacks invalides, transactions bloquées et écarts de réconciliation.

## Questions bloquantes avant Phase 7/8

Fournisseurs/contrats officiels, environnement sandbox, frais, règles de remboursement, surpaiement, allocation, numérotation légale, contenu/cachet du reçu, échéanciers et responsabilités d'approbation doivent être validés.
