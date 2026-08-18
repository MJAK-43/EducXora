# Exigences, hypothèses et questions ouvertes

## Sources et ordre d'autorité

1. Instructions explicites les plus récentes du propriétaire (mission du 18/08/2026).
2. `EduXora_CahierDesCharges.pdf`, v1.0, 2025, référentiel fonctionnel.
3. ADR acceptés et documentation de phase.

Une exigence récente étend le PDF (SaaS multi-centres, Super Admin, SaaS billing, audit détaillé). Elle ne supprime pas silencieusement la V1 fonctionnelle. Les questions ci-dessous gardent les décisions réversibles.

## Exigences consolidées

- SaaS avec isolation stricte de chaque centre ; multi-sites d'un même centre reste hors V1.
- Quatre profils de départ : Directeur, Secrétaire/Caissière, Enseignant et Super Admin plateforme, implémentés par vrai RBAC backend.
- Dossiers apprenants, inscriptions, groupes, planning avec conflits, présences et corrections auditées.
- Test de placement allemand V1, niveaux CECR A1–C2, questions système verrouillées et questions centre.
- Factures, échéanciers, paiements totaux/partiels, espèces, Mobile Money, remboursements, reçus et impayés.
- Notifications SMS/WhatsApp/email/in-app asynchrones et traçables.
- Dashboard et exports utiles, autorisés et tenant-scoped.
- Pointage partiellement offline avec synchronisation idempotente et conflits explicites.
- Responsive web/mobile, iOS 13+/Android 8+, accessibilité WCAG 2.2 AA raisonnable.
- Audit, sauvegardes, environnements séparés, CI qualité/sécurité et documentation.

## Registre des décisions métier ouvertes

| ID | Question / incohérence | Hypothèse de conception réversible | À trancher avant |
|---|---|---|---|
| Q-001 | V1 uniquement allemand, mais modèle demandé pour plusieurs langues | catalogue multilingue ; seul le test allemand activé V1 | Phase 4/9 |
| Q-002 | Web + application native/hybride dans le PDF, mais PWA en Phase 12 | web responsive/PWA d'abord ; wrapper natif soumis à décision produit | Phase 12 |
| Q-003 | Un utilisateur dans un seul centre V1 ; Super Admin transverse | adhésion unique active pour métier ; Super Admin hors tenant | Phase 2 |
| Q-004 | Email ou téléphone comme identifiant : vérification, unicité, partage familial | normaliser les deux ; au moins un vérifié ; ne pas rendre le téléphone apprenant unique | Phase 2/4 |
| Q-005 | Correction validée par Directeur seulement (p.6) ou Directeur/Secrétaire (p.6) | permission `attendance.correct`, accordée provisoirement aux deux, audit/motif | Phase 6 |
| Q-006 | Groupe présenté obligatoire, mais assignation possible après test | groupe nullable jusqu'à décision de placement | Phase 4/9 |
| Q-007 | Algorithme et seuils A1–C2 absents | stratégie versionnée/configurable, aucune valeur hardcodée | Phase 9 |
| Q-008 | Sélection de 15–30 questions, durée, tentative, reprise et fraude absentes | versionner un blueprint de test ; décision produit requise | Phase 9 |
| Q-009 | Coefficients, périodes, arrondis et seuils de bulletin absents | règles versionnées par centre avec défauts validés | Phase 9 |
| Q-010 | Qui valide un changement de niveau saisi par l'enseignant ? | proposition enseignant, validation Directeur par défaut | Phase 9 |
| Q-011 | Conditions d'échéance et définition retard/impayé | échéance explicite ; retard 1–30 jours, impayé >30 jours, fuseau centre | Phase 7 |
| Q-012 | Échec MoMo « reste En attente » | tentative `FAILED`, facture/dette toujours ouverte ; aucun reçu | Phase 8 |
| Q-013 | Surpaiement, allocation multi-factures et ordre d'affectation absents | refuser surpaiement ; allocation explicite FIFO à confirmer | Phase 7 |
| Q-014 | Politique de remboursement, approbation et pièces absentes | motif obligatoire, montant borné, permission renforcée, trace immuable | Phase 7/8 |
| Q-015 | Numérotation facture/reçu, taxes et mentions légales absentes | séquence par centre/année, contenu final après validation comptable locale | Phase 7 |
| Q-016 | Fournisseurs MTN/Orange, sandbox, signatures et frais inconnus | contrats provider + fake ; aucune intégration réelle sans documentation/credentials sandbox | Phase 8 |
| Q-017 | Conflits de planning : bornes, récurrence, fuseau | intervalles `[début, fin)`, centre timezone ; récurrence matérialisée | Phase 5 |
| Q-018 | Notification tous les 3 jours : consentement, horaires, arrêt | préférences/consentements, fenêtre locale et arrêt au paiement ; règles à valider | Phase 10 |
| Q-019 | Données de mineurs, tuteurs et consentement photo | minimisation ; modèle contact/tuteur activé si confirmé | Phase 4 |
| Q-020 | Rétention finance/audit/messages/documents | pas de suppression destructive avant politique légale validée | Phase 2/7/10 |
| Q-021 | « données jamais perdues », disponibilité 99 %, backup quotidien sans RPO/RTO | proposer RPO ≤24 h hors transactions et RTO à définir ; journal provider/réconciliation | Phase 14 |
| Q-022 | 50 utilisateurs : par centre ou plateforme, charge exacte | baseline plateforme avec parcours mixtes, puis mesure pilote | Phase 1/14 |
| Q-023 | Accès support/impersonation Super Admin | désactivé par défaut ; si activé, justifié, borné, visible et audité | Phase 3 |
| Q-024 | Suspension abonnement : accès aux données et paiements en cours | lecture/export Directeur, écritures bloquées sauf résolution paiement ; à valider | Phase 13 |
| Q-025 | Cachet sur reçu : image, signature, validation | asset privé du centre, snapshot dans document, droits de modification | Phase 3/7 |

## Risques majeurs

- planning de trois mois incompatible avec l'ampleur V1 sans équipe/capacité définie ; prioriser MUST et pilotes ;
- APIs/agréments Mobile Money et WhatsApp peuvent imposer délais externes ; démarrer contractualisation tôt ;
- iOS 13 augmente coût de compatibilité et limite certaines briques CSS/PWA ;
- offline + corrections concurrentes est un domaine à forte complexité, à prototyper avant Phase 12 ;
- exigences légales camerounaises (fiscalité, reçus, données, consentements) non auditées ; expertise locale requise ;
- 99 % et absence absolue de perte exigent SLO, RPO/RTO et budget d'infrastructure explicites ;
- données financières, exports et accès Super Admin concentrent les risques de fraude/fuite.

## Décisions confirmées par cadrage technique

- expiration inactive 30 min **et** durée absolue 24 h sont cumulatives ;
- montant XAF en entier, sans flottant ;
- temps stocké UTC et rendu selon le fuseau centre ;
- Tailwind 4 non compatible avec iOS 13 et donc non retenu ;
- les contraintes cross-tenant sont renforcées en base, pas seulement dans l'UI/API ;
- un paiement Mobile Money n'est confirmé qu'après preuve fournisseur authentifiée.
