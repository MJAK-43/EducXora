# Architecture Decision Records

Les ADR consignent les décisions structurantes de Phase 0. États : `Accepted` guide l'implémentation ; `Provisional` doit être validé par preuve ou décision avant la phase indiquée ; `Superseded` renvoie vers son remplacement.

| ADR | Décision | Statut |
|---|---|---|
| [ADR-001](ADR-001-modular-monolith.md) | monolithe modulaire Laravel | Accepted |
| [ADR-002](ADR-002-multitenancy.md) | schéma partagé et isolation tenant en profondeur | Accepted |
| [ADR-003](ADR-003-authentication-rbac.md) | adhésions, RBAC et Super Admin séparé | Accepted |
| [ADR-004](ADR-004-frontend-css.md) | Inertia/Vue et CSS compatible iOS 13 | Accepted |
| [ADR-005](ADR-005-finance-payments.md) | écritures finance séparées et providers idempotents | Accepted |
| [ADR-006](ADR-006-offline-attendance.md) | commandes offline idempotentes et conflits explicites | Accepted |
| [ADR-007](ADR-007-async-integrations.md) | queues et adapters pour effets externes | Accepted |
| [ADR-008](ADR-008-data-audit.md) | UUIDv7, XAF entier et audit append-only | Accepted |
| [ADR-009](ADR-009-deployment.md) | Docker et déploiements immuables | Provisional |

Format d'un nouvel ADR : contexte, décision, conséquences positives/négatives, alternatives et critères de révision. Ne pas réécrire une décision acceptée après coup ; la remplacer par un nouvel ADR.
