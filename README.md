# Plateforme de Gestion Académique — EPF Africa (Backend)

API REST Laravel pour la plateforme numérique de gestion du cycle académique de l'EPF Africa : stages, encadrements, mémoires, soutenances, jurys, bibliothèque numérique et notifications.

Ce document décrit l'ensemble du projet — cahier des charges, architecture, modules, règles métier, RBAC — ainsi que les travaux réalisés lors de la dernière session de développement.

---

## 1. Contexte du projet

L'EPF Africa souhaite remplacer ses procédures administratives manuelles (stages, mémoires, soutenances) par une plateforme numérique centralisée offrant traçabilité, réduction des délais et indicateurs de pilotage pour la direction.

Le cahier des charges définit 6 modules fonctionnels (stages, encadrements, mémoires, soutenances, bibliothèque, tableaux de bord) et 6 profils d'acteurs :

| Acteur | Rôle principal |
|---|---|
| Administrateur Général | Super-administrateur, paramétrage, gestion des comptes et droits |
| Responsable de formation | Validation des sujets, suivi global, gestion des encadrements |
| Responsable relation entreprise | Gestion des entreprises partenaires (CRUD), suivi des stages — sans validation ni affectation d'encadreur |
| Enseignant Encadreur | Suivi des étudiants, évaluations, corrections |
| Étudiant | Dépôt de candidatures, suivi de stage, dépôt de mémoires |
| Entreprise Partenaire | Convention, évaluation du stagiaire |
| Jury de Soutenance | Notation, délibération, validation des résultats |

Ce backend implémente précisément le sous-ensemble du cahier des charges couvrant les rôles **Étudiant**, **Enseignant Encadreur**, **Administration** et **Jury de Soutenance**, ainsi que le système de notifications transversal et l'archivage automatique en bibliothèque.

---

## 2. Stack technique

- **Framework** : Laravel 13 (structure sans `Http/Kernel.php`, configuration dans `bootstrap/app.php`)
- **PHP** : ^8.3
- **Authentification API** : Laravel Sanctum (tokens Bearer)
- **RBAC** : `spatie/laravel-permission` (rôles + permissions, tables `roles`/`permissions`/`model_has_roles`)
- **Base de données** : MySQL (dev), SQLite possible (`.env.example` par défaut)
- **PDF** : `barryvdh/laravel-dompdf` (procès-verbaux)
- **Exports** : `maatwebsite/excel`
- **Autorisation** : Policies Laravel + `$this->authorize()` dans les contrôleurs — **aucune middleware `role:`/`permission:` sur les routes**, tout est géré au niveau contrôleur/policy.

---

## 3. Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
# configurer DB_* dans .env (mysql ou sqlite)
php artisan migrate
php artisan db:seed --class=RoleAndPermissionSeeder
php artisan serve
```

Planification des tâches (rappel de soutenance) :

```bash
php artisan schedule:work   # en développement
# ou un cron système appelant `php artisan schedule:run` chaque minute en production
```

---

## 4. Modèle de données (tables principales)

| Table | Rôle |
|---|---|
| `users`, `etudiants`, `enseignants` | Comptes et profils (un `User` a au plus un profil `Etudiant` ou `Enseignant`) |
| `entreprises` | Entreprises partenaires |
| `stages`, `stage_journal_entries` | Stages affectés et leur journal de bord |
| `candidatures_stage` | Candidatures étudiantes (CV + lettre de motivation) avant affectation |
| `rapports_stage` | Rapports intermédiaires/finaux de stage |
| `encadrements`, `encadrement_entries`, `encadrement_rendez_vous` | Encadrement étudiant↔enseignant (type `stage` ou `memoire`), fil de discussion, rendez-vous |
| `memoires`, `memoire_versions`, `memoire_corrections` | Sujets de mémoire, versions (V1…finale), annotations/corrections |
| `soutenances`, `soutenance_jury`, `soutenance_notes`, `proces_verbaux` | Soutenances, composition du jury (avec fenêtre d'accès), grille de notation, PV |
| `creneaux_soutenance` | Créneaux proposés par l'administration, réservables par les étudiants |
| `documents` | Bibliothèque numérique (archivage des mémoires soutenus) |
| `notifications` | Notifications applicatives (indépendantes du canal `Notifiable` de Laravel) |
| `roles`, `permissions`, `model_has_roles`, `role_has_permissions` | RBAC (spatie/laravel-permission) |

**Convention de clé étrangère à connaître** : `memoires`/`soutenances`/`soutenance_jury` référencent directement `users.id` (`etudiant_id`, `encadreur_id`, `user_id`), tandis que `stages`/`encadrements`/`rapports_stage` référencent les **profils** `etudiants.id`/`enseignants.id`. Les deux conventions coexistent délibérément et sont respectées dans tout le code.

---

## 5. Rôles et permissions (RBAC)

Rôles seedés (`database/seeders/RoleAndPermissionSeeder.php`) :

| Rôle | Permissions clés |
|---|---|
| `admin_general` | Toutes les permissions |
| `responsable_formation` | Stages (suivre/valider/affecter), encadrements, validation mémoires, planification soutenances, bibliothèque |
| `responsable_relation_entreprise` | Entreprises (CRUD complet), suivi des stages — pas de validation/affectation |
| `enseignant_encadreur` | Suivi/évaluation des stages, correction des mémoires, consultation bibliothèque |
| `etudiant` | Candidature de stage, proposition de sujet, dépôt de versions, consultation |
| `entreprise_partenaire` | Évaluation du stagiaire |
| `jury_soutenance` | Notation des soutenances (attribué automatiquement lors de la constitution d'un jury) |

L'autorisation repose sur des **Policies** (`app/Policies/*.php`), auto-découvertes par convention de nommage (`App\Models\Foo` ↔ `App\Policies\FooPolicy`). Chaque contrôleur appelle `$this->authorize(...)`. Les policies combinent permission globale (`$user->can(...)`) et vérification d'ownership (ex. l'encadreur ne peut agir que sur ses propres étudiants/mémoires).

---

## 6. Modules fonctionnels et règles métier implémentées

### 6.1 Étudiant

- Ne peut **ni créer** un stage **ni modifier** une affectation — toute affectation par l'administration est définitive.
- Postule à une offre via `CandidatureStage` (CV + lettre de motivation) ; l'administration décide de l'affectation.
- Consulte ses stages, gère son journal de bord, dépose ses rapports intermédiaires.
- Propose un sujet de mémoire (soumis à validation de l'encadreur déjà assigné) ou reçoit un sujet proposé directement par l'encadreur (démarrage immédiat, sans validation).
- Dépose les versions successives de son mémoire (V1, V2, V3, finale), consulte l'historique, les annotations et le pourcentage d'avancement défini par l'encadreur.
- À partir de **80 % d'avancement**, peut demander un créneau de soutenance parmi les disponibilités de l'administration.
- **Verrouillage automatique** : 5 jours avant la date officielle de soutenance, le dépôt de nouvelles versions est bloqué (`Memoire::estVerrouillePourSoutenance()`).
- Ne peut pas réserver un créneau situé à moins de 5 jours (`CreneauSoutenance::peutEtreReserve()`).
- Consulte les informations de sa soutenance (compte à rebours) puis ses résultats une fois publiés.

### 6.2 Enseignant Encadreur

- Ne peut ni créer ni affecter un stage.
- Consulte la liste et les stages de ses étudiants encadrés.
- Propose un sujet de mémoire, ou examine un sujet proposé par un étudiant (accepter / demander des modifications / refuser).
- Annote les mémoires, ajoute commentaires/recommandations, définit le pourcentage d'avancement.
- Valide la version finale (exige ≥ 80 % d'avancement) — rend l'étudiant éligible à la planification de sa soutenance.
- Organise des rendez-vous et communique avec ses étudiants via le fil de discussion de l'encadrement.

### 6.3 Administration

- Gère les comptes étudiants, enseignants et jury ; attribue le rôle encadreur.
- Affecte étudiants↔encadreurs, affecte un stage à un étudiant (action définitive), associe une entreprise.
- Sélectionne les mémoires éligibles à la soutenance (version finale validée).
- Valide les demandes de créneau, planifie les soutenances, constitue les jurys (attribution automatique du rôle `jury_soutenance` + fenêtre d'accès), génère les convocations, publie les résultats.
- **Ne peut jamais modifier les notes attribuées par le jury.**
- Seuls les comptes jury ont une durée de validité limitée — exprimée **par soutenance** (`soutenance_jury.date_debut_acces/date_fin_acces`), pas au niveau du compte, pour permettre la réactivation d'un même juré sur une autre soutenance avec une nouvelle fenêtre.

### 6.4 Jury de soutenance

- Accès sécurisé, actif uniquement pendant la fenêtre définie par l'administration (`SoutenanceJury::estActif()`).
- Consulte les soutenances qui lui sont attribuées et les mémoires associés.
- Note selon la grille d'évaluation (schéma générique `critère/note/commentaire`, un enregistrement par critère).
- **Verrouille définitivement** ses notes (`validerMesNotes`) — non modifiables ensuite (`SoutenanceJury::peutNoter()` devient faux).
- Une fois la soutenance terminée ou l'accès expiré, plus aucune action possible ; l'administration peut **réactiver** un juré pour une autre soutenance (`SoutenanceJury::reactiver()`).

### 6.5 Bibliothèque numérique (auto-archivage)

Dès que tous les membres du jury ont validé leurs notes et que l'administration publie les résultats (`SoutenanceController::publierResultats`), le mémoire est **automatiquement archivé** dans `documents` (titre, auteur, année, mention, fichier de la dernière version). Le module complet de recherche/consultation (module 7 du cahier des charges) n'est pas dans le périmètre de cette implémentation — seul l'effet de bord d'archivage l'est.

### 6.6 Notifications

`app/Services/NotificationService.php` centralise l'envoi de notifications applicatives (table `notifications`, indépendante du canal `Notifiable` par défaut de Laravel). Déclenchées aux points clés du workflow :

| Destinataire | Événements |
|---|---|
| Étudiant | affectation stage/encadreur, nouveau commentaire/RDV, mémoire validé final, sujet à modifier, document annoté, soutenance planifiée/validée, convocation disponible, résultats publiés |
| Encadreur | nouveau mémoire déposé, réponse étudiant, nouvelle proposition de sujet, demande de créneau, convocation générée |
| Administration (`admin_general` + `responsable_formation`) | nouvelle candidature, validation finale d'un mémoire, soutenance programmée, tous les jurés ont noté |
| Jury | nouvelle soutenance, convocation, rappel avant soutenance, procès-verbal généré |

Le rappel avant soutenance est envoyé par la commande planifiée `soutenances:rappel` (quotidienne, `routes/console.php`), pour les soutenances à J-2/J-1.

`jury_indisponible` existe dans le service mais n'est pas câblé : le cahier des charges ne définit pas de flux permettant à un juré de se déclarer indisponible.

---

## 7. Aperçu de l'API

Toutes les routes (hors `/login`) sont protégées par `auth:sanctum`. ~120 routes au total. Groupes principaux :

- `POST /login`, `POST /logout`, `GET /me`
- `GET|POST /stages`, `/stages/{id}/valider|affecter-encadreur|journal|cloturer`
- `GET|POST /encadrements`, `/encadrements/{id}/entree|rendez-vous|cloturer`
- `apiResource /memoires` + `/valider|rejeter|demander-modification|affecter-encadreur`
- `/memoires/{id}/versions`, `/versions/{id}/corriger|valider-finale|download`
- `apiResource /soutenances` + `/jury|convocations|publier-resultats`
- `/soutenances/{id}/notes` (grille de notation), `/soutenances/{id}/proces-verbaux/*`
- `apiResource /entreprises`
- `apiResource /candidatures-stage` + `/affecter-stage`
- `apiResource /rapports-stage` + `/corriger|valider|download`
- `apiResource /creneaux-soutenance` + `/reserver|annuler|valider`, `GET /creneaux-soutenance-disponibles`
- `GET /etudiants`, `GET /etudiants/{id}`, `GET /mon-espace/*` (self-service étudiant)
- `GET /mes-etudiants-encadres`, `/etudiants-encadres/{id}/stage` (self-service encadreur)
- `POST /administration/*` (comptes, rôles, association entreprise, éligibilité soutenance, jury)
- `GET|POST /jury/*` (self-service jury : mes-soutenances, mémoire, accès, valider-notes)
- `GET|POST /notifications/*`
- `GET /dashboard/*` (indicateurs, exports PDF/Excel/CSV)

Liste exhaustive : `php artisan route:list --path=api`.

---

## 8. État du projet et travaux réalisés

Au démarrage de la dernière session, 9 contrôleurs, 4 modèles et 6 migrations existaient **non commités**, écrits en avance de leur câblage, et présentaient de nombreuses incohérences (routes absentes, policies manquantes, incohérences de schéma). Les travaux suivants ont été réalisés pour livrer un backend cohérent et fonctionnel, conforme au cahier des charges pour les rôles Étudiant/Encadreur/Administration/Jury :

**Couche modèle**
- Ajout des relations et méthodes métier manquantes : `Memoire::peutDemanderSoutenance()` / `estVerrouillePourSoutenance()`, `Soutenance::estTerminee()` / `tousLesJuryOntNote()`, `SoutenanceJury::estActif()` / `peutNoter()` / `reactiver()`, `Etudiant::peutPostulerCandidature()`, `Enseignant::etudiantsEncadres()` / `capaciteAtteinte()`, etc.
- Mise à jour des `fillable`/`casts` pour les colonnes déjà migrées mais jamais exploitées (`memoire_versions.pourcentage_avancement`, `soutenance_jury.date_debut_acces`, etc.).
- **Correction de 3 bugs de table** : `CreneauSoutenance`, `CandidatureStage` et `RapportStage` ne déclaraient pas `$table`, Eloquent devinait donc un nom de table erroné (`creneau_soutenances` au lieu de `creneaux_soutenance`, etc.) — détecté par test.
- Retrait du trait `Notifiable` de `User` (en collision avec la table `notifications` applicative custom) et ajout de `notifications()`/`notificationsNonLues()`/`soutenanceJury()`.

**RBAC**
- `StagePolicy::create` : retire la capacité de l'étudiant à créer un stage (changement de règle métier explicite du cahier des charges).
- `MemoirePolicy::valider` : autorise désormais l'encadreur assigné (pas seulement admin/responsable formation) à valider/refuser un sujet proposé par l'étudiant.
- `SoutenancePolicy::noter` : bloque la notation une fois les notes verrouillées ou l'accès jury expiré.
- 6 nouvelles policies créées : `EtudiantPolicy`, `EnseignantPolicy`, `CandidatureStagePolicy`, `RapportStagePolicy`, `CreneauSoutenancePolicy`, `UserPolicy`.
- Uniformisation du rôle jury : `jury_soutenance` partout (l'ébauche utilisait `jury` par endroits).

**Contrôleurs — suppression des doublons, une seule source de vérité par action**
- `AdministrationController` réduit de ~20 méthodes à 11 : la planification de soutenance, la composition du jury, les convocations et la publication des résultats sont désormais gérées uniquement par `SoutenanceController` (enrichi) ; le triage des candidatures par `CandidatureStageController`.
- `EncadreurController` et `EtudiantController` réduits à leurs endpoints réellement uniques ; la proposition/validation de sujet passe par `MemoireController`, l'annotation/avancement/validation finale par `MemoireVersionController`, la messagerie par `EncadrementController`.
- `JuryController` réécrit : la notation utilise désormais le schéma réel `critère/note/commentaire` (`SoutenanceNoteController`, déjà correct) au lieu d'un schéma à 4 colonnes inexistant en base ; ajout de `validerMesNotes()` pour le verrouillage définitif.
- `StageController::store` devient admin-only et affecte directement (statut `valide`), conformément à la nouvelle règle métier.

**Notifications**
- Toutes les méthodes de `NotificationService` (déjà écrites mais jamais appelées) sont désormais déclenchées aux points du workflow concernés.
- Ajout de la commande planifiée `soutenances:rappel` (`app/Console/Commands/RappelSoutenances.php`) pour le rappel J-2/J-1 aux jurés.

**Bibliothèque**
- Archivage automatique (`Document::firstOrCreate`) du mémoire dans `SoutenanceController::publierResultats`.

**Routes**
- Câblage de 9 contrôleurs jusqu'alors orphelins dans `routes/api.php` (~120 routes au total, sans duplication).

**Migration**
- `2026_07_17_000007_add_notes_validees_to_soutenance_jury_table.php` : ajoute `notes_validees`/`notes_validees_a` pour le verrouillage définitif des notes par juré.

**Vérification effectuée**
- `php artisan route:list` sans erreur (120 routes).
- `Gate::getPolicyFor()` confirmé pour chaque nouveau modèle.
- `grep` de `hasRole('jury')` (singulier) : zéro résultat résiduel.
- Lint PHP (`php -l`) propre sur tous les fichiers touchés.
- Smoke test de bout en bout (16 assertions, transaction annulée, aucune donnée persistée) couvrant : refus de création de stage par l'étudiant, pré-remplissage de l'encadreur lors d'une proposition de sujet étudiant, seuil de 80 %, verrouillage à 5 jours, réservation de créneau, cycle de vie complet du jury (actif → notation → verrouillage → réactivation).

---

## 9. Hors périmètre / limitations connues

- Module bibliothèque complet (recherche multicritère, consultation) — seul l'archivage automatique est implémenté.
- Notification `jury_indisponible` : service prêt mais non déclenché (pas de flux défini dans le cahier des charges pour qu'un juré se déclare indisponible).
- Tableaux de bord et indicateurs (module 8) : `DashboardController` existant, non modifié lors de cette session.
- Aucune suite de tests automatisés (PHPUnit) n'existe encore pour les modules décrits en section 8 ; la vérification a été faite manuellement (voir ci-dessus).
- MFA (authentification à double facteur), chiffrement applicatif des données sensibles et sauvegardes automatiques (exigences sécurité du cahier des charges, section 10) ne sont pas couverts par cette implémentation.

---

## 10. Ressources Laravel

Documentation générale du framework : [laravel.com/docs](https://laravel.com/docs). Ce projet est basé sur le squelette standard `laravel/laravel` ; voir `composer.json` pour la liste complète des dépendances.
