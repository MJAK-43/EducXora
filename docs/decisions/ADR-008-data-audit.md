# ADR-008 — Identifiants, monnaie, temps et audit

- Statut : Accepted
- Date : 2026-08-18

## Contexte

Les documents et APIs exposent des identifiants, la finance XAF exige exactitude et les changements sensibles doivent être reproductibles. Les centres camerounais utilisent le même fuseau aujourd'hui mais le SaaS peut s'étendre.

## Décision

UUIDv7 pour entités exposables ; montants `bigint` + devise ; instants UTC `timestamptz`, fuseau configurable par centre avec défaut `Africa/Douala`. L'audit est append-only, structuré, expurgé et corrélé. Les règles configurables (placement, coefficients) sont versionnées et référencées par les résultats historiques.

## Conséquences

Exactitude, tri temporel et reproductibilité améliorés. Les rapports convertissent explicitement fuseau/devise. L'audit ne doit pas devenir une copie incontrôlée de PII.

## Alternatives écartées

Float pour montants ; heure locale sans fuseau ; UUID aléatoire uniquement ; logs texte comme audit ; modification en place des règles historiques.
