# Design system EduXora

## Implémentation Phase 1

Les tokens sont implémentés dans `resources/css/tokens.css`, les styles globaux dans `base.css`, `components.css` et `layout.css`, et assemblés par `app.css`. PostCSS Preset Env et Autoprefixer ciblent notamment `iOS >= 13`. Aucune police distante, aucune image lourde, Tailwind et aucune bibliothèque UI généraliste ne sont utilisés.

Le catalogue local `/dev/ui` documente visuellement typographie, palette, espacements et composants. Le middleware `local.only` retourne 404 hors environnement local.

Composants livrés : `UiButton`, `UiIconButton`, `UiInput`, `UiTextarea`, `UiSelect`, `UiCheckbox`, `UiRadio`, `UiSwitch`, `UiBadge`, `UiAlert`, `UiCard`, `UiDivider`, `UiSpinner`, `UiSkeleton`, `UiAvatar`, `UiTooltip`, `UiModal`, `UiDropdown` et `UiEmptyState`. L'App Shell comprend `AppLayout`, `AppSidebar`, `AppTopbar`, `AppMain` et `PageHeader`.

Les icônes proviennent exclusivement de `@lucide/vue`. Une icône décorative est masquée aux technologies d'assistance ; tout bouton icône exige un libellé accessible.

## Direction

Une interface calme, dense juste ce qu'il faut et centrée sur les décisions opérationnelles. Pas de template admin générique, de glassmorphism, de gradients décoratifs ni d'ombres lourdes. Les composants utilisent une seule grammaire visuelle et rendent le statut par texte, icône et couleur.

## Compatibilité CSS

La cible iOS 13 interdit Tailwind CSS 4 : son support officiel commence à Safari 16.4. La décision Phase 0 est un socle CSS natif organisé, traité par PostCSS/Autoprefixer avec `browserslist` incluant `iOS >= 13`, et composants Vue. Les fonctionnalités non disponibles sur Safari 13 (`flex gap`, `:focus-visible`, container queries, etc.) ont des fallbacks testés. Tailwind 3.4 n'est pas retenu par défaut ; il ne sera évalué que si sa matrice réelle passe les tests.

## Tokens initiaux

Ces familles de valeurs sont désormais la base Phase 1 ; le fichier source exhaustif reste `resources/css/tokens.css`.

```css
:root {
  --color-background: #f6f8fb;
  --color-surface: #ffffff;
  --color-surface-muted: #eef2f6;
  --color-border: #d7dee8;
  --color-text-primary: #172033;
  --color-text-secondary: #526071;
  --color-primary: #2457d6;
  --color-primary-hover: #1d46ad;
  --color-success: #147a55;
  --color-warning: #9a5700;
  --color-danger: #b42318;
  --color-info: #1769aa;
  --color-focus: #6d4aff;

  --space-1: 0.25rem;
  --space-2: 0.5rem;
  --space-3: 0.75rem;
  --space-4: 1rem;
  --space-6: 1.5rem;
  --space-8: 2rem;
  --space-12: 3rem;

  --radius-sm: 0.375rem;
  --radius-md: 0.625rem;
  --radius-lg: 0.875rem;
  --shadow-sm: 0 1px 2px rgb(23 32 51 / 8%);
  --shadow-md: 0 8px 24px rgb(23 32 51 / 10%);
}
```

Prévoir pour chaque couleur fonctionnelle un fond pâle, un texte contrasté et une icône. Les tokens dark mode ne font pas partie de V1 sauf décision produit.

## Typographie et iconographie

Police système rapide (`Inter` seulement si auto-hébergée et budgétée), base 16 px, corps avec interligne 1.5, titres compacts. Échelle : 12/14/16/20/24/32 px. Les montants utilisent chiffres tabulaires. Icônes SVG d'une seule bibliothèque, 16/20/24 px, toujours nommées pour lecteur d'écran si elles portent du sens.

## Mise en page

Breakpoints de conception : 390, 768, 1024, 1280 px ; les tests ajoutent 375 et 1440 px. Contenu plafonné selon page, zones tactiles minimales 44×44 px. Sidebar desktop compacte et groupée ; navigation mobile en panneau accessible avec actions métier prioritaires. Topbar contient breadcrumb, actions contextuelles, notifications et compte.

## Composants fondamentaux

- `BaseButton`, `IconButton`, `BaseLink` ;
- `TextField`, `SelectField`, `CheckboxField`, `DateField`, `MoneyField`, `PhoneField` ;
- `FieldError`, `FormSection`, `ActionBar` ;
- `StatusBadge`, `Alert`, `Toast`, `EmptyState`, `Skeleton`, `ProgressState` ;
- `Dialog`, `Drawer`, `Dropdown`, `Tabs` ;
- `DataTable`, `FilterBar`, `Pagination`, `MobileRecordCard` ;
- `AppShell`, `Sidebar`, `Topbar`, `Breadcrumb` ;
- `StatCard`, `AttentionList`, visualisations métier justifiées.

Chaque composant documente variantes, états hover/focus/disabled/loading/error, clavier et usage mobile. Aucun double submit : bouton bloqué et requête idempotente lorsque sensible.

## DataTable responsive

Desktop : en-tête persistant si utile, tri annoncé, filtres visibles, pagination serveur et actions non ambiguës. Mobile : colonnes prioritaires en cartes structurées, détails secondaires dépliables et actions dans un menu nommé ; l'overflow horizontal n'est qu'un dernier recours pour matrices intrinsèques.

## Formulaires

Labels toujours visibles, aide avant erreur, erreur reliée par `aria-describedby`, focus sur le premier champ invalide, sections courtes et action principale stable. Les formats XAF, téléphone +237 et date française sont affichés sans altérer la valeur canonique serveur.

## Accessibilité et mouvement

WCAG 2.2 AA visé : contraste 4,5:1 pour texte normal, 3:1 pour grand texte/composants, focus épais et visible, ordre DOM logique, titres hiérarchiques, modales piégeant/restaurant le focus. Une règle `:focus` de base reste active sur Safari 13 ; `:focus-visible` améliore lorsque supporté. Animations 120–220 ms, transform/opacity seulement, et désactivation avec `prefers-reduced-motion`.

## Questions visuelles auxquelles répondre

Un graphique n'est ajouté que s'il répond à une question (« les encaissements baissent-ils ? », « quels impayés agir maintenant ? »). Les KPI montrent période, unité et définition. Les nombres n'emploient jamais couleur seule pour signifier leur statut.
