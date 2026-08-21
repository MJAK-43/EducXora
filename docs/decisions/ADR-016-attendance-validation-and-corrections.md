# ADR-016 — Feuilles de présence, validation et corrections

- Statut : Accepted
- Date : 2026-08-21
- Phase : 5 — Suivi des présences

## Contexte

La Phase 4 fournit déjà `CourseSession`, le groupe, l’enseignant responsable et les affectations historisées des apprenants. La présence doit réutiliser cette séance, résister aux écritures concurrentes, rester isolée par organisation et conserver toute correction postérieure à une validation.

## Décision

Une `attendance_sheet` unique par `(organization_id, course_session_id)` représente le pointage d’une séance existante. Elle conserve les références du groupe et de l’adhésion enseignante au moment de sa création afin que les changements ultérieurs du planning ne réécrivent pas l’historique. Elle suit uniquement `draft` puis `validated` ; une correction ne rouvre pas la feuille.

Au démarrage, l’action verrouille la séance et construit côté serveur le roster depuis les affectations actives au début de la séance. Une affectation commencée après la séance ou terminée avant celle-ci est exclue. Le frontend ne peut ni choisir le tenant, ni substituer la séance, le groupe ou l’enseignant, ni injecter un apprenant absent de ce roster.

`learner_attendances` stocke un relevé unique par feuille et apprenant. `teacher_attendances` stocke séparément le relevé unique de l’enseignant de la feuille. Les statuts sont `present`, `absent` et `excused`, portés par `AttendanceStatus` et renforcés par des contraintes SQL.

La validation s’exécute dans une transaction avec verrous sur la feuille et la séance. Elle exige un statut pour chaque apprenant du roster et une présence enseignant, refuse une séance annulée, puis écrit atomiquement `validated_at` et `validated_by`. Une seconde validation et toute écriture de brouillon après validation sont refusées au backend.

Une correction validée exige la permission `attendance.correct`, un nouveau statut différent et un motif non vide. Elle crée une ligne append-only `attendance_corrections` contenant sujet, ancienne valeur, nouvelle valeur, auteur, motif et date, puis met à jour le relevé courant sous le même verrou transactionnel. L’audit générique conserve en parallèle l’événement de sécurité et ses références minimales ; la table métier est nécessaire pour restituer un historique structuré sans dépendre du format de métadonnées de l’audit.

Le taux apprenant est calculé à la demande sur une période bornée. Le numérateur contient uniquement `present`. Le dénominateur contient tous les relevés de feuilles validées rattachées à des séances encore `scheduled`, y compris `absent` et `excused`. Une absence justifiée n’est donc pas une présence. Les séances annulées sont exclues.

Les permissions atomiques sont `attendance.view`, `attendance.take`, `attendance.validate`, `attendance.correct` et `attendance.view_reports`. Les profils administratifs et Staff disposent du périmètre tenant complet. Un enseignant consulte, pointe et valide uniquement les séances associées à sa propre adhésion. Accountant n’a aucun accès Attendance.

## Isolation et concurrence

- toutes les tables portent `organization_id` non nullable et utilisent `BelongsToTenant` ;
- les relations tenant-scoped sont protégées par des FK composites incluant l’organisation ;
- les UUID de route sont résolus après les middlewares tenant ;
- les unicités feuille/séance, relevé apprenant et relevé enseignant sont garanties en base ;
- démarrage, validation et correction utilisent transaction et `FOR UPDATE` ;
- les index couvrent séance, groupe, enseignant, apprenant, statut, validation et dates d’historique.

## Préparation offline

Le mode offline, IndexedDB, Service Worker et endpoints de synchronisation ne font pas partie de cette phase. Les UUID stables, les opérations transactionnelles et l’absence de réouverture d’une feuille validée permettent une future enveloppe de commande avec `command_uuid`, version de base et idempotence. Cette future phase devra ajouter son journal de commandes et ses règles de conflit sans contourner les invariants présents.

## Conséquences

Le modèle est plus explicite qu’un simple audit JSON et nécessite quatre tables. En contrepartie, les lectures historiques, la présence enseignant, les corrections et les futurs rapports restent requêtables, contraints et testables. Les exports PDF/XLSX et le mode offline restent volontairement différés.

## Alternatives écartées

- créer une seconde entité de cours : doublon incompatible avec `CourseSession` ;
- recalculer le roster depuis les membres actuels à chaque affichage : perte de vérité historique ;
- modifier silencieusement un relevé validé : absence de traçabilité métier ;
- stocker un taux dérivé : risque de désynchronisation et absence de filtre temporel fiable.
