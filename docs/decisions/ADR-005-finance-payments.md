# ADR-005 — Finance append-only et orchestration Mobile Money

- Statut : Accepted
- Date : 2026-08-18

## Contexte

Facture, tentative et paiement sont souvent confondus, créant reçus indus et doublons de webhook. EduXora doit gérer paiements partiels, espèces, deux providers et remboursements.

## Décision

Modéliser séparément facture, échéancier, tentative, transaction provider, paiement confirmé, allocation, remboursement et reçu. Les écritures confirmées sont immuables/compensées. Providers derrière `PaymentProvider`. Webhooks authentifiés et uniques ; confirmation transactionnelle idempotente sous verrou. Finance apprenant séparée du billing SaaS.

## Conséquences

Audit et réconciliation fiables, au prix de davantage d'entités et de machines d'état. Les règles d'allocation, remboursement et numérotation doivent être décidées avant Phase 7. Les appels externes restent asynchrones et testés par fake.

## Alternatives écartées

Une table `payments` universelle ; statut payé sur facture sans ledger ; logique MTN/Orange en contrôleur ; reçu à l'initiation.
