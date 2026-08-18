# ADR-004 — Frontend Inertia/Vue et CSS compatible iOS 13

- Statut : Accepted
- Date : 2026-08-18

## Contexte

Le produit est une application métier authentifiée, avec quelques endpoints d'intégration/offline. La cible impose Vue 3/TypeScript et iOS 13+. Tailwind CSS 4 exige officiellement Safari 16.4+ et ne satisfait donc pas la contrainte.

## Décision

Utiliser Inertia avec Vue 3 Composition API, TypeScript strict et Vite. Construire le design system sur CSS natif organisé + PostCSS/Autoprefixer et composants Vue, avec browserslist iOS 13+. Maintenir une API ciblée pour webhooks, sync offline et futur mobile. Pinia seulement pour état global démontré.

## Conséquences

Moins de duplication frontend/API et contrôle précis de compatibilité. Le design system demande une gouvernance de composants/tokens. Les fonctionnalités CSS modernes doivent avoir fallback. La stratégie sera reconsidérée si iOS 13 est abandonné.

## Sources

- Laravel 13 release notes : https://laravel.com/docs/13.x/releases
- Tailwind compatibility : https://tailwindcss.com/docs/compatibility
