# Audit UI EduXora — 2026

## 1. Cadre et méthode

Audit réalisé sur le code local, sans commit, tag, push, rebase, merge ni modification des stashes. Les sources de vérité examinées sont le cahier des charges, les exigences, les ADR, le design system, les routes Laravel, les contrôleurs, Form Requests, policies, permissions, composants Inertia/Vue et tests automatisés.

La validation visuelle réelle par navigateur aux largeurs 375, 390, 768, 1024, 1280 et 1440 px est **NOT TESTED** : le runtime navigateur automatisé n’était pas disponible. Le code responsive et les contrôles statiques ne remplacent pas cette validation. La capture fournie par l’utilisateur confirme uniquement l’écran de connexion sur bureau.

La Phase 6 présente dans le working tree reste en cours et hors validation de cet audit. Elle n’a pas été modifiée fonctionnellement ni déclarée validée.

## 2. Principes de conception retenus

- Afficher uniquement des données réelles issues du tenant courant.
- Faire coïncider visibilité UI, permissions atomiques et policies backend.
- Donner la priorité aux tâches quotidiennes : planning, pointage, apprenants et groupes.
- Utiliser les tokens et composants existants, sans nouvelle palette ni Tailwind.
- Rendre chaque état explicite : vide, succès, erreur, lecture seule, annulé, brouillon ou validé.
- Conserver des cibles tactiles de 44 px minimum et une information indépendante de la couleur.
- Réduire la densité sur mobile sans supprimer les actions autorisées.

## 3. Inventaire écrans, routes et capacités

| Domaine | Écrans Vue | Routes / actions backend | Permission ou protection | État |
| --- | --- | --- | --- | --- |
| Accueil | Home | GET / | public | Couvert |
| Erreurs sûres | vues Blade 403, 404 et 500 | rendu des exceptions HTTP | aucune donnée sensible exposée | Couvert |
| Authentification | Login, Register, ForgotPassword, ResetPassword, VerifyEmail, ConfirmPassword, AcceptInvitation | login, register, reset, vérification, confirmation, invitation | guest/auth, throttling, signature, session fraîche | Couvert |
| Sélection tenant | Organizations/Select | GET/POST /organizations/select | adhésion active serveur | Couvert |
| Tableau de bord | Dashboard | GET /dashboard | tenant membre actif | **Refondu** |
| Apprenants | Index, Create, Show, Edit | CRUD, archive/restaure, photo privée, export, historiques | learners.view/create/update/archive/export | Couvert |
| Groupes | Index, Create, Show, Edit, gestion des affectations | CRUD, archive/restaure, attach/detach | group.view/create/update/archive, group.assign_learners | Couvert |
| Planning | Index, Create, Edit | CRUD et annulation de séances | schedule.view/create/update/cancel | Couvert |
| Présences | Index, Take, Show, LearnerHistory, TeacherHistory | démarrage, brouillon, validation, corrections, historiques | attendance.view/take/validate/correct | Couvert |
| Utilisateurs | Organization/Users/Index | liste, invitation, rôles, activation | users.view/invite/update/delete | Couvert |
| Rôles | Organization/Roles/Index | gestion des rôles tenant | roles.view/create/update/delete | Couvert |
| Organisation | Organization/Settings | paramètres du centre | organization.view/update + confirmation mot de passe | Couvert |
| Audit | Organization/Audit/Index | GET /organization/audit | audit.view + AuditLogPolicy + filtrage organization_id | **Créé** |
| Plateforme | Platform/Organizations/Index | gestion organisations | platform.admin + confirmation mot de passe | Couvert |
| Catalogue interne | Dev/UiKit | /dev/ui, local uniquement | environnement local | Couvert |
| Pédagogie Phase 6 | questions, test, revue, historique niveau | routes /pedagogy et extension apprenant | permissions placement_* | En cours, hors validation |

## 4. Matrice backend vers interface

| Capacité backend validée | Point d’entrée UI | Observation |
| --- | --- | --- |
| Isolation tenant et adhésion active | sélecteur d’organisation + layout | Aucun center_id transmis par les écrans métier |
| RBAC apprenants | menu, pages apprenants, actions conditionnelles | Aligné |
| RBAC groupes | menu, pages groupes, affectations | Aligné |
| RBAC planning | menu, planning, création/édition/annulation | Aligné |
| RBAC présences | menu, feuilles et historiques | Aligné |
| Audit append-only | nouveau journal d’audit en lecture seule | Écart fermé |
| Audit sur tableau de bord | activité récente si audit.view | Écart fermé |
| Données opérationnelles journalières | nouveau tableau de bord tenant-scoped | Ancien placeholder supprimé |
| Rôles de l’adhésion courante | topbar | Libellé réel, plus de texte générique |
| Messages flash Laravel | alerte globale du layout | Écart fermé |
| Téléchargement photo privé | fiche apprenant | Aligné |
| Export apprenants throttlé | liste apprenants | Aligné |

## 5. Matrice interface vers backend

Les liens ajoutés pointent uniquement vers des routes existantes ou créées dans ce lot :

- « Voir le planning » et « Tout voir » vers /schedule ;
- groupes vers /groups/{uuid} ;
- apprenants vers /learners/{uuid} ;
- feuilles de présence vers /attendance/{uuid} ;
- journal et activité vers /organization/audit ;
- organisation, utilisateurs et rôles restent sur leurs routes existantes.

Aucun bouton décoratif, aucune statistique fictive et aucun lien vers un module futur n’ont été ajoutés. Les sections non autorisées ne sont pas rendues.

## 6. Modèle de navigation

La barre latérale est structurée en quatre niveaux :

1. Vue d’ensemble : tableau de bord.
2. Gestion quotidienne : apprenants, groupes, planning, présences et, tant qu’elle existe dans le working tree, pédagogie.
3. Administration : utilisateurs, rôles, journal d’audit, organisation.
4. Plateforme : organisations, uniquement pour le Super Admin.

Chaque entrée tenant dépend désormais de la permission de lecture correspondante. La page active expose aria-current="page". Sur mobile, le tiroir et son backdrop existants sont conservés.

## 7. UX par rôle

| Rôle | Expérience attendue |
| --- | --- |
| Organization Admin | vision opérationnelle complète, administration, rôles, audit et réglages |
| Manager | pilotage apprenants/groupes/planning/présences et utilisateurs selon permissions ; aucun audit si audit.view absent |
| Teacher | séances et groupes assignés, pointage et vues autorisées ; aucune donnée administrative masquée côté serveur |
| Staff | opérations quotidiennes prévues par ses permissions ; aucune section d’administration non autorisée |
| Accountant | accès limité aux capacités explicitement accordées, notamment apprenants |
| Super Admin | contexte plateforme et capacités complètes explicites |

Le tableau de bord filtre les séances et groupes au membership enseignant lorsque schedule.create est absent, suivant la règle déjà appliquée au planning.

## 8. Responsive

Les points de rupture restent exprimés avec les tokens et conventions CSS existants :

- 1280–1440 : quatre KPI et grilles multi-colonnes ;
- 768–1024 : KPI sur deux colonnes, contenu principal sur une colonne ;
- 375–390 : KPI et panneaux sur une colonne, lignes de séance flexibles, filtres d’audit empilés et boutons pleine largeur.

Les tableaux métier existants ont déjà leurs variantes empilées/cartes. À vérifier visuellement : débordements de noms longs, sélecteur d’organisation, menu mobile, badges dans les séances et pagination audit.

## 9. Accessibilité

Améliorations incluses :

- navigation principale nommée et entrée active avec aria-current ;
- sélecteur d’organisation avec label accessible ;
- filtres audit avec labels visibles ;
- journal annoncé comme contenu mis à jour ;
- flash global utilisant le composant UiAlert et un rôle adapté ;
- liens d’action textuels, états de présence avec libellés et pas seulement une couleur ;
- hiérarchie h1/h2 conservée ;
- lien d’évitement existant conservé ;
- respect des styles de focus et reduced-motion du design system.

Dette à vérifier avec navigateur et lecteur d’écran : ordre du focus dans le tiroir, retour du focus après fermeture, contraste effectif rendu, zoom 200 %, reflow 320 px et annonces Inertia après navigation.

## 10. Composants et cohérence visuelle

Réutilisés : AppLayout, AppSidebar, AppTopbar, PageHeader, UiCard, UiBadge, UiAlert, UiEmptyState, UiAvatar et UiIconButton. Le tableau de bord introduit ses motifs de KPI et listes en style scoped afin de ne pas étendre inutilement l’API du design system.

Aucune nouvelle valeur de couleur de marque, police, dépendance visuelle ou composant dupliqué n’a été introduit.

## 11. Écrans créés

- Organization/Audit/Index.vue : journal tenant-scoped paginé, filtrable et en lecture seule.

## 12. Écrans et layouts refondus

- Dashboard.vue : KPI réels, séances du jour, synthèse présence, groupes, apprenants récents et activité selon permissions.
- AppSidebar.vue : sections métier, visibilité par permission, état actif accessible.
- AppTopbar.vue : rôle réel et sélecteur d’organisation explicite.
- AppLayout.vue : feedback global après mutations.

Les écrans Learners, Groups, Schedule, Attendance, Auth et Organization ont été audités. Ils utilisent déjà les composants, états, policies et comportements responsive attendus ; aucune réécriture sans bénéfice démontré n’a été faite.

## 13. Écrans manquants ou dette restante

- Page 419/session expirée dédiée : non identifiée ; les vues sûres 403, 404 et 500 sont présentes et couvertes par les tests Foundation.
- Préférences de profil utilisateur : aucune capacité backend correspondante auditée, donc aucun écran inventé.
- Recherche globale et notifications : capacités backend absentes, donc non ajoutées.
- Visual regression et tests navigateur : absents.
- Pagination partagée : plusieurs implémentations locales encore présentes.
- Phase 6 : en cours et explicitement hors validation.

## 14. Sécurité et confidentialité UI

Le journal d’audit utilise AuditLogPolicy, un Form Request et une requête qui impose organization_id. Il ne renvoie ni metadata, ni IP, ni user-agent, afin de minimiser l’exposition. Le tableau de bord ne retourne les apprenants et activités que lorsque les permissions correspondantes existent. Les modèles tenant-scoped conservent leur scope central.

## 15. Tests ajoutés

- UiExperienceTest : état opérationnel vide du dashboard, isolation tenant du journal et refus sans audit.view.
- DashboardExperience.spec.ts : rendu des KPI/données réelles et absence des jeux de données non autorisés.

## 16. Limites de validation

La conformité fonctionnelle automatisée peut être déclarée uniquement sur les commandes réellement exécutées et rapportées. La conformité visuelle WCAG 2.2 AA complète exige encore une session navigateur réelle sur les six largeurs, un contrôle clavier, un lecteur d’écran et une mesure de contraste sur le rendu final.
