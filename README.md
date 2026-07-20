# Plateforme de Gestion Académique — EPF Africa (Backend + intégration Frontend)

API REST Laravel pour la plateforme numérique de gestion du cycle académique de l'EPF Africa : stages, encadrements, mémoires, soutenances, jurys, bibliothèque numérique et notifications. Ce document décrit l'architecture, les règles métier, le RBAC, l'historique des sessions de développement, **et** la manière dont le frontend React consomme (ou devrait consommer) cette API.

---

## 1. Contexte du projet

L'EPF Africa souhaite remplacer ses procédures administratives manuelles (stages, mémoires, soutenances) par une plateforme numérique centralisée offrant traçabilité, réduction des délais et indicateurs de pilotage pour la direction.

Une première version du cahier des charges envisageait 6 profils d'acteurs (Administrateur Général, Responsable de formation, Responsable relation entreprise, Enseignant Encadreur, Étudiant, Entreprise Partenaire, Jury de Soutenance). **Cette architecture a été abandonnée** : le nombre de rôles est désormais **fixé définitivement à 4** :

1. **Étudiant**
2. **Enseignant Encadreur**
3. **Administration** (fusionne les anciens rôles Administrateur Général / Responsable de formation / Responsable relation entreprise)
4. **Jury de Soutenance**

Les entreprises partenaires restent une donnée gérée par l'administration (CRUD), mais ne sont **pas** un rôle utilisateur connecté.

---

## 2. Stack technique

### Backend
- **Framework** : Laravel 13 (structure sans `Http/Kernel.php`, configuration dans `bootstrap/app.php`)
- **PHP** : ^8.3
- **Authentification API** : Laravel Sanctum (tokens Bearer)
- **RBAC** : `spatie/laravel-permission` (rôles + permissions, tables `roles`/`permissions`/`model_has_roles`)
- **Base de données** : MySQL (dev)
- **Email** : `Illuminate\Support\Facades\Mail`, driver `log` en dev (`MAIL_MAILER=log` dans `.env`) — envoi **synchrone**, pas de queue pour l'instant
- **PDF** : `barryvdh/laravel-dompdf` (procès-verbaux)
- **Exports** : `maatwebsite/excel`
- **Autorisation** : Policies Laravel + `$this->authorize()` dans les contrôleurs — **aucune middleware `role:`/`permission:` sur les routes**, tout est géré au niveau contrôleur/policy. Seule exception : `EnsureAccountIsActive`, un middleware global (groupe `api`) qui ne fait *que* vérifier la période de validité du compte (voir §6.4), pas les rôles.

### Frontend
- **Framework** : React 19 + Vite, routage `react-router-dom` v7
- **HTTP** : `axios` via un client central (`src/shared/api/apiClient.js`)
- **Auth** : token Sanctum stocké dans `localStorage`, contexte React (`AuthContext`)
- Voir §9 pour le détail de l'intégration frontend↔backend, y compris les écarts connus.

---

## 3. Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
# configurer DB_* dans .env (mysql)
php artisan migrate
php artisan db:seed --class=RoleAndPermissionSeeder
php artisan serve
```

Planification des tâches (rappel de soutenance) :

```bash
php artisan schedule:work   # en développement
# ou un cron système appelant `php artisan schedule:run` chaque minute en production
```

Frontend :

```bash
cd ../PFE_FRONT
npm install
npm run dev   # attend le backend sur http://127.0.0.1:8000 (baseURL codée en dur dans apiClient.js)
```

---

## 4. Modèle de données (tables principales)

| Table | Rôle |
|---|---|
| `users`, `etudiants`, `enseignants` | Comptes et profils (un `User` a au plus un profil `Etudiant` ou `Enseignant`). `users` porte aussi `must_change_password`, `date_debut_acces`, `date_expiration` (voir §6.3/6.4) |
| `entreprises` | Entreprises partenaires |
| `stages`, `stage_journal_entries` | Stages affectés et leur journal de bord |
| `candidatures_stage` | Candidatures étudiantes (CV + lettre de motivation) avant affectation |
| `rapports_stage` | Rapports intermédiaires/finaux de stage |
| `encadrements`, `encadrement_entries`, `encadrement_rendez_vous` | Encadrement étudiant↔enseignant (type `stage` ou `memoire`), fil de discussion, rendez-vous |
| `memoires`, `memoire_versions`, `memoire_corrections` | Sujets de mémoire, versions (V1…finale), annotations/corrections. `memoires` porte `eligible_soutenance` + `date_eligibilite_soutenance` (voir §6.1) |
| `soutenances`, `soutenance_jury`, `soutenance_notes`, `proces_verbaux` | Soutenances, composition du jury (avec fenêtre d'accès par affectation), grille de notation, PV |
| `creneaux_soutenance` | Créneaux proposés par l'administration, réservables par les étudiants |
| `documents` | Bibliothèque numérique (archivage automatique des mémoires soutenus) |
| `notifications` | Notifications applicatives (indépendantes du canal `Notifiable` de Laravel) |
| `roles`, `permissions`, `model_has_roles`, `role_has_permissions` | RBAC (spatie/laravel-permission) — exactement 4 rôles, voir §5 |

**Convention de clé étrangère à connaître** : `memoires`/`soutenances`/`soutenance_jury` référencent directement `users.id` (`etudiant_id`, `encadreur_id`, `user_id`), tandis que `stages`/`encadrements`/`rapports_stage`/`candidatures_stage` référencent les **profils** `etudiants.id`/`enseignants.id`. Les deux conventions coexistent délibérément et sont respectées dans tout le code.

---

## 5. Rôles et permissions (RBAC) — 4 rôles, définitif

Rôles seedés (`database/seeders/RoleAndPermissionSeeder.php`) :

| Rôle (slug) | Nom métier | Permissions clés |
|---|---|---|
| `etudiant` | Étudiant | Candidature de stage, proposition de sujet, dépôt de versions, consultation, bibliothèque |
| `enseignant_encadreur` | Enseignant Encadreur | Suivi/évaluation des stages, correction des mémoires, consultation bibliothèque |
| `administration` | Administration | **Toutes les permissions** (`Permission::all()`) — absorbe les anciens rôles `admin_general`, `responsable_formation`, `responsable_relation_entreprise` |
| `jury_soutenance` | Jury de Soutenance | Notation des soutenances (attribué automatiquement lors de la constitution d'un jury), consultation bibliothèque |

`entreprise_partenaire` (rôle dormant, jamais utilisé dans le code) a été supprimé du seeder — les entreprises ne sont plus qu'une donnée gérée par l'administration.

L'autorisation repose sur des **Policies** (`app/Policies/*.php`), auto-découvertes par convention de nommage (`App\Models\Foo` ↔ `App\Policies\FooPolicy`). Chaque contrôleur appelle `$this->authorize(...)`. Les policies combinent permission globale (`$user->can(...)`/`$user->hasRole('administration')`) et vérification d'ownership (ex. l'encadreur ne peut agir que sur ses propres étudiants/mémoires).

---

## 6. Modules fonctionnels et règles métier implémentées

### 6.1 Étudiant

- Ne peut **ni créer** un stage **ni modifier** une affectation — toute affectation par l'administration est définitive.
- Postule à une offre via `CandidatureStage` (CV + lettre de motivation) ; l'administration décide de l'affectation.
- Consulte ses stages, gère son journal de bord, dépose ses rapports intermédiaires.
- Propose un sujet de mémoire (soumis à validation de l'encadreur déjà assigné) ou reçoit un sujet proposé directement par l'encadreur (démarrage immédiat, sans validation).
- Dépose les versions successives de son mémoire (V1, V2, V3, finale), consulte l'historique, les annotations et le pourcentage d'avancement défini par l'encadreur.
- **Éligibilité à la soutenance** : ce n'est **pas** un seuil automatique sur le pourcentage d'avancement. `Memoire::peutDemanderSoutenance()` exige `statut === 'valide_final'` **ET** `eligible_soutenance === true` — un champ à part, mis à `true` uniquement par un geste explicite et distinct de l'encadreur (`POST /memoires/{id}/accorder-eligibilite-soutenance`, voir §6.2). Le pourcentage d'avancement (`memoire_versions.pourcentage_avancement`) n'ouvre plus rien automatiquement.
- Une fois éligible, choisit un créneau parmi les disponibilités de l'administration (ou est rattaché automatiquement s'il n'en existe qu'un seul — logique côté frontend à implémenter, le backend renvoie simplement la liste disponible).
- **Verrouillage automatique** : 5 jours avant la date officielle de soutenance, le dépôt de nouvelles versions est bloqué (`Memoire::estVerrouillePourSoutenance()`).
- Ne peut pas réserver un créneau situé à moins de 5 jours (`CreneauSoutenance::peutEtreReserve()`).
- Consulte les informations de sa soutenance (compte à rebours) puis ses résultats une fois publiés.
- Reçoit ses identifiants par email à la création de son compte (voir §6.3) et doit changer son mot de passe à la première connexion (`must_change_password`).

### 6.2 Enseignant Encadreur

- Ne peut ni créer ni affecter un stage.
- Consulte la liste et les stages de ses étudiants encadrés.
- Propose un sujet de mémoire, ou examine un sujet proposé par un étudiant (accepter / demander des modifications / refuser).
- Annote les mémoires, ajoute commentaires/recommandations, définit le pourcentage d'avancement (`MemoireVersionController::corriger`).
- Valide la version finale (exige ≥ 80 % d'avancement, `MemoireVersionController::validerFinale`) — condition nécessaire mais **non suffisante** pour la soutenance.
- **Accorde le droit à la soutenance** (`MemoireController::accorderEligibiliteSoutenance`) — action **distincte** de la validation de version finale, réservée à l'encadreur affecté à ce mémoire (`MemoirePolicy::accorderEligibiliteSoutenance`). C'est cette action, et elle seule, qui rend l'étudiant capable de réserver un créneau.
- Organise des rendez-vous et communique avec ses étudiants via le fil de discussion de l'encadrement.

### 6.3 Administration

- Gère les comptes étudiants, enseignants et jury.
- **Génération automatique des mots de passe** : à la création d'un compte (étudiant, enseignant ou jury), aucun mot de passe n'est saisi par l'administration. Le backend génère un mot de passe aléatoire (`Str::random(12)`), le hache (bcrypt) avant stockage, et envoie l'email `UserCredentialsMail` (login + mot de passe temporaire + lien de connexion) — le mot de passe en clair ne vit qu'en mémoire le temps de cet envoi, jamais persisté ni loggé. Le compte est marqué `must_change_password = true`.
  - Envoi **synchrone** pour l'instant (volume actuel limité) ; à faire passer par une file d'attente (`ShouldQueue`) si le volume de créations simultanées augmente.
  - L'administration peut réinitialiser le mot de passe de n'importe quel compte (`POST /administration/users/{user}/reinitialiser-mot-de-passe`) : même principe, nouveau mot de passe généré et emailé.
- **Affectation stage → encadreur : deux actions successives et distinctes, jamais simultanées.**
  1. `CandidatureStageController::affecterStage` crée le `Stage` (l'action n'accepte plus d'`encadreur_id` — retiré volontairement).
  2. `StageController::affecterEncadreur` (ou `EncadrementController::store`) affecte ensuite l'encadreur ; `EncadrementController::store` refuse explicitement de créer un encadrement de type `stage` tant qu'aucun stage actif n'existe pour l'étudiant.
- Associe une entreprise, sélectionne les mémoires éligibles à la soutenance (version finale validée), valide les demandes de créneau, planifie les soutenances, constitue les jurys (attribution automatique du rôle `jury_soutenance` + fenêtre d'accès par soutenance), génère les convocations, publie les résultats.
- **Ne peut jamais modifier les notes attribuées par le jury.**
- **Comptes jury à durée de validité** : seuls ces comptes ont une période de validité limitée, exprimée à deux niveaux complémentaires :
  - `soutenance_jury.date_debut_acces`/`date_fin_acces` (par affectation à une soutenance donnée — préexistant).
  - `users.date_debut_acces`/`date_expiration` (au niveau du compte lui-même, posé à la création via `creerCompteJury` et mis à jour à la réactivation via `reactiverJury`) — bloque la connexion globale, indépendamment de toute soutenance précise.
  - Les comptes étudiant/enseignant n'ont jamais de date d'expiration.

### 6.4 Jury de soutenance

- Accès sécurisé, actif uniquement pendant la fenêtre définie par l'administration, vérifiée à deux niveaux :
  - `SoutenanceJury::estActif()` pour la notation d'une soutenance précise ;
  - le middleware global `EnsureAccountIsActive` (`app/Http/Middleware/EnsureAccountIsActive.php`, enregistré sur le groupe `api` dans `bootstrap/app.php`), qui invalide le token Sanctum et renvoie 401 dès que `User::estExpire()` (date d'expiration dépassée) ou `User::accesPasEncoreActif()` (accès pas encore ouvert) est vrai.
- Consulte les soutenances qui lui sont attribuées et les mémoires associés.
- Note selon la grille d'évaluation (schéma générique `critère/note/commentaire`, un enregistrement par critère).
- **Verrouille définitivement** ses notes (`validerMesNotes`) — non modifiables ensuite, ni par le jury ni par l'administration (`SoutenanceJury::peutNoter()` devient faux).
- Une fois la soutenance terminée ou l'accès expiré, plus aucune action possible ; l'administration peut **réactiver** un juré pour une autre soutenance (`SoutenanceJury::reactiver()` + mise à jour de `users.date_debut_acces`/`date_expiration`).

### 6.5 Bibliothèque numérique

- **Auto-archivage** : dès que tous les membres du jury ont validé leurs notes et que l'administration publie les résultats (`SoutenanceController::publierResultats`), le mémoire est automatiquement archivé dans `documents` (titre, auteur, année, mention, fichier de la dernière version).
- **Accessible à tous les rôles, sans filtre** : `DocumentController`/`DocumentPolicy` gèrent la consultation/recherche/téléchargement, gatés uniquement par la permission `bibliotheque.consulter` — que possèdent les 4 rôles (choix d'ouverture académique assumé, pas une erreur : aucun filtre par rôle n'est appliqué à la consultation).
- Les routes (`apiResource('documents', ...)` + `GET documents/{id}/download`) étaient écrites mais **orphelines** (aucune route déclarée) avant cette session ; elles sont désormais câblées dans `routes/api.php`.

### 6.6 Notifications

`app/Services/NotificationService.php` centralise l'envoi de notifications applicatives (table `notifications`, indépendante du canal `Notifiable` par défaut de Laravel). Déclenchées aux points clés du workflow :

| Destinataire | Événements |
|---|---|
| Étudiant | affectation stage/encadreur, nouveau commentaire/RDV, mémoire validé final, sujet à modifier, document annoté, soutenance planifiée/validée, convocation disponible, résultats publiés, **candidature retenue** (nouveau) |
| Encadreur | nouveau mémoire déposé, réponse étudiant, nouvelle proposition de sujet, demande de créneau, convocation générée |
| Administration (rôle `administration`) | nouvelle candidature, validation finale d'un mémoire, soutenance programmée, tous les jurés ont noté |
| Jury | nouvelle soutenance, convocation, rappel avant soutenance, procès-verbal généré |

Le rappel avant soutenance est envoyé par la commande planifiée `soutenances:rappel` (quotidienne, `routes/console.php`).

`jury_indisponible` existe dans le service mais n'est pas câblé : aucun flux ne permet à un juré de se déclarer indisponible.

**Corrections apportées cette session** : `NotificationService::affectationStage()` existait déjà mais n'était jamais appelé — désormais déclenché dans `CandidatureStageController::affecterStage()`. Ajout d'une notification `candidature_retenue` envoyée à l'étudiant quand l'administration fait passer sa candidature au statut `retenue`.

---

## 7. Aperçu de l'API

Toutes les routes (hors `/login`) sont protégées par `auth:sanctum` + le middleware global `EnsureAccountIsActive`. 129 routes au total (`php artisan route:list --path=api` pour la liste exhaustive). Groupes principaux :

- `POST /login`, `POST /logout`, `GET /me`, `POST /changer-mot-de-passe`
- `GET|POST /stages`, `/stages/{id}/valider|affecter-encadreur|journal|cloturer`
- `GET|POST /encadrements`, `/encadrements/{id}/entree|rendez-vous|cloturer`
- `apiResource /memoires` + `/valider|rejeter|demander-modification|affecter-encadreur|accorder-eligibilite-soutenance`
- `/memoires/{id}/versions`, `/versions/{id}/corriger|valider-finale|download`
- `apiResource /soutenances` + `/jury|convocations|publier-resultats`
- `/soutenances/{id}/notes` (grille de notation), `/soutenances/{id}/proces-verbaux/*`
- `apiResource /documents` + `/documents/{id}/download` (bibliothèque, ouvert aux 4 rôles)
- `apiResource /entreprises`
- `apiResource /candidatures-stage` + `/affecter-stage`
- `apiResource /rapports-stage` + `/corriger|valider|download`
- `apiResource /creneaux-soutenance` + `/reserver|annuler|valider`, `GET /creneaux-soutenance-disponibles`
- `GET /etudiants`, `GET /etudiants/{id}`, `GET /mon-espace/*` (self-service étudiant : stages, stage-actif, memoires, encadrements, candidatures, rapports, soutenance, resultats-soutenance)
- `GET /mes-etudiants-encadres`, `/etudiants-encadres/{id}/stage` (self-service encadreur)
- `POST /administration/*` (comptes étudiants/enseignants/jury, rôle encadreur, association entreprise, mémoires éligibles, réactivation jury, **réinitialisation mot de passe**)
- `GET|POST /jury/*` (self-service jury : mes-soutenances, mémoire, accès, valider-notes)
- `GET|POST /notifications/*`
- `GET /dashboard/*` (indicateurs, exports PDF/Excel/CSV)

---

## 8. Historique des sessions de développement

### 8.1 Session initiale — câblage général (avant consolidation RBAC)

Au démarrage de cette session, 9 contrôleurs, 4 modèles et 6 migrations existaient non commités, écrits en avance de leur câblage, avec de nombreuses incohérences (routes absentes, policies manquantes, incohérences de schéma). Travaux réalisés :

- **Modèles** : ajout des relations/méthodes métier manquantes (`Memoire::peutDemanderSoutenance()`/`estVerrouillePourSoutenance()`, `Soutenance::estTerminee()`/`tousLesJuryOntNote()`, `SoutenanceJury::estActif()`/`peutNoter()`/`reactiver()`, `Etudiant::peutPostulerCandidature()`, etc.). Correction de 3 bugs de table (`$table` manquant sur `CreneauSoutenance`, `CandidatureStage`, `RapportStage`). Retrait du trait `Notifiable` de `User` (collision avec la table `notifications` applicative).
- **RBAC** : retrait de la capacité étudiant à créer un stage ; l'encadreur assigné peut désormais valider/refuser un sujet proposé par son étudiant ; 6 nouvelles policies créées.
- **Contrôleurs** : suppression des doublons (`AdministrationController` réduit de ~20 à 11 méthodes, planification/jury/convocations/résultats déplacés vers `SoutenanceController`) ; `JuryController` réécrit pour utiliser le vrai schéma de notation `critère/note/commentaire`.
- **Notifications** : câblage de toutes les méthodes de `NotificationService` (déjà écrites mais jamais appelées) ; ajout de la commande `soutenances:rappel`.
- **Bibliothèque** : archivage automatique à la publication des résultats.
- **Routes** : câblage de 9 contrôleurs orphelins (~120 routes).

### 8.2 Session consolidation RBAC (4 rôles) + règles complémentaires (2026-07-19)

Point de départ : le seeder définissait encore 7 rôles (`admin_general`, `responsable_formation`, `responsable_relation_entreprise`, `enseignant_encadreur`, `etudiant`, `entreprise_partenaire`, `jury_soutenance`), en contradiction avec la règle définitive à 4 rôles. Travaux réalisés :

1. **Consolidation RBAC** : seeder réduit à 4 rôles (`etudiant`, `enseignant_encadreur`, `administration`, `jury_soutenance`) ; remplacement de `admin_general` → `administration` et suppression de `responsable_formation` dans ~25 emplacements (9 policies, `AdministrationController`, `DashboardController`, `ProcesVerbalController`, `NotificationService`, les deux seeders) ; suppression de `responsable_relation_entreprise` (une seule référence, `StagePolicy`) et de `entreprise_partenaire` (rôle mort). Suppression du fichier mort `app/Http/Controllers/JuryController.php` (vide, hors namespace `Api`).
2. **Éligibilité à la soutenance** : nouveau champ `memoires.eligible_soutenance` (+ `date_eligibilite_soutenance`), découplé du pourcentage d'avancement ; nouvelle action `MemoireController::accorderEligibiliteSoutenance` + ability de policy dédiée.
3. **Mots de passe** : génération automatique (`Str::random(12)`) + `must_change_password` (nouvelle colonne `users`) + `app/Mail/UserCredentialsMail.php` (envoi synchrone) ; endpoint admin de réinitialisation ; endpoint self-service `changer-mot-de-passe`.
4. **Comptes jury à durée de validité** : `users.date_debut_acces`/`date_expiration`, `User::estExpire()`/`accesPasEncoreActif()`, middleware `EnsureAccountIsActive` enregistré globalement (il existait déjà mais n'était pas branché et appelait une méthode `estExpire()` qui n'existait pas encore).
5. **Bibliothèque** : câblage des routes `documents` (contrôleur + policy déjà écrits mais orphelins), ajout de `bibliotheque.consulter` au rôle jury (manquant, nécessaire pour l'accès sans restriction de rôle).
6. **Ordre stage → encadreur** : retrait de `encadreur_id` de `CandidatureStageController::affecterStage` (il permettait une affectation simultanée) ; garde-fou dans `EncadrementController::store` ; câblage de `NotificationService::affectationStage()` (jamais appelé) et ajout d'une notification `candidature_retenue`.

**Vérification effectuée** : `php -l` propre sur tous les fichiers touchés/créés ; `php artisan route:list --path=api` sans erreur (129 routes, toutes les nouvelles routes résolues) ; migrations non exécutées faute d'accès à la base de données de dev (service MySQL arrêté, redémarrage hors périmètre de la session) — **à exécuter manuellement** (`php artisan migrate`) avant de tester en local.

---

## 9. Intégration Frontend (React)

### 9.1 Structure

```
PFE_FRONT/src/
├── shared/
│   ├── api/apiClient.js        # instance axios, baseURL http://127.0.0.1:8000/api (codée en dur), intercepteurs token + 401
│   ├── api/*.js                # un module par domaine (étudiant, encadreur, administration, jury, notifications, candidatures, créneaux, rapports, bibliothèque)
│   ├── auth/AuthContext.jsx    # login/logout/me, token en localStorage ("api_token"), expose hasRole()
│   ├── auth/ProtectedRoute.jsx # garde de route par rôle (roles[].name)
│   ├── auth/LoginPage.jsx
│   └── roleHomePath.js         # redirige "/" vers /etudiant|/encadreur|/administration|/jury selon le rôle
└── modules/
    ├── etudiant|encadreur|administration|jury/pages/*Dashboard.jsx   # tableaux de bord par rôle (widgets + stats)
    └── stages|encadrements|memoires|soutenances|entreprises|bibliotheque/pages/*ListPage.jsx  # pages CRUD génériques, accessibles à /stages, /memoires, etc.
```

Le rôle `administration` et `jury_soutenance` utilisés côté frontend (`ProtectedRoute`, `roleHomePath`) correspondent bien aux slugs définitifs du backend consolidé (§5) — pas de désalignement sur ce point précis.

### 9.2 Ce qui est correctement câblé

Les pages génériques `*ListPage.jsx` (`/stages`, `/encadrements`, `/memoires`, `/soutenances`, `/entreprises`, `/bibliotheque`) appellent `apiClient` directement avec les **vraies** routes backend (ex. `SoutenancesListPage.jsx` appelle `GET /soutenances`, `POST /soutenances/{id}/jury`, `POST /soutenances/{id}/publier-resultats` — routes qui existent réellement). Les modules `bibliothequeApi.js`, `candidaturesApi.js`, `rapportsApi.js` sont également alignés avec les routes réelles (`/documents`, `/candidatures-stage`, `/rapports-stage`).

### 9.3 Écart connu : les dashboards par rôle appellent des routes qui n'existent pas

Les modules `etudiantApi.js`, `encadreurApi.js`, `administrationApi.js`, `juryApi.js` et `notificationsApi.js` (utilisés par les 4 `*Dashboard.jsx`) ciblent un schéma de routes **différent** de celui réellement exposé par le backend — probablement écrit contre une version antérieure/aspirationnelle de l'API. Exemples concrets :

| Frontend appelle | Route backend réelle |
|---|---|
| `GET /etudiant/mes-stages` | `GET /mon-espace/stages` |
| `GET /etudiant/mon-stage-actif` | `GET /mon-espace/stage-actif` |
| `POST /jury/soutenance/{id}/note` | `POST /soutenances/{id}/notes` |
| `POST /jury/soutenance/{id}/note/valider` | `POST /jury/soutenances/{id}/valider-notes` |
| `POST /administration/planifier-soutenance` | `POST /soutenances` |
| `POST /administration/soutenance/{id}/constituer-jury` | `POST /soutenances/{id}/jury` |
| `POST /administration/soutenance/{id}/publier-resultats` | `POST /soutenances/{id}/publier-resultats` |
| `GET /notifications/count` | `GET /notifications/non-lues/count` |
| `GET /creneaux-soutenance/disponibles-pour-moi` | `GET /creneaux-soutenance-disponibles` |

Conséquence concrète : les 4 tableaux de bord par rôle (`EtudiantDashboard`, `EncadreurDashboard`, `AdministrationDashboard`, `JuryDashboard`) échouent silencieusement sur plusieurs de leurs appels (la plupart sont enveloppés dans `.catch(() => ({ data: [] }))`, donc l'écran ne plante pas mais affiche des zéros/listes vides au lieu des vraies données). De plus, sur `AdministrationDashboard`, les boutons "Planifier une soutenance", "Affecter un stage", "Traiter les candidatures", "Gérer les entreprises" n'ont **aucun** gestionnaire `onClick` — seule la création de comptes (via `CreerCompteModal`) est branchée. Les actions réellement fonctionnelles pour l'administration passent aujourd'hui par les pages génériques `/soutenances`, `/stages`, `/candidatures-stage`, `/entreprises`, pas par le dashboard dédié.

**Ce n'est pas quelque chose que cette session a corrigé** — la consigne était de documenter l'existant, pas de réaligner le frontend. La correction consiste soit à ajuster les 5 modules `api/*.js` cités pour cibler les vraies routes ci-dessus (rapide, pas de changement d'architecture), soit à ajouter côté backend des alias de routes correspondant aux chemins attendus par le frontend (déconseillé : duplique la surface d'API pour un gain nul).

### 9.4 Ce que le frontend ne gère pas encore

- `must_change_password` : aucun écran de changement de mot de passe forcé n'existe côté frontend (l'endpoint `POST /changer-mot-de-passe` existe côté backend, §6.3, mais n'est appelé par aucun module frontend actuel).
- Réservation de créneau (bouton "Réserver" dans `EtudiantDashboard`) : pas de gestionnaire `onClick` branché.
- Octroi de l'éligibilité à la soutenance (`accorder-eligibilite-soutenance`, §6.2) : aucun appel frontend pour l'instant, action à ajouter côté `encadreurApi.js`/UI encadreur.

---

## 10. Hors périmètre / limitations connues

- Module bibliothèque complet (recherche multicritère, consultation) — CRUD + recherche basique implémentés côté backend (§6.5) ; l'UI frontend dédiée (`BibliothequeListPage`) existe mais n'a pas été auditée dans cette session.
- Notification `jury_indisponible` : service prêt mais non déclenché (pas de flux défini pour qu'un juré se déclare indisponible).
- Tableaux de bord et indicateurs (`DashboardController`) : non modifiés lors des deux sessions décrites ici.
- Écart de routes frontend/backend décrit en §9.3 — non corrigé, à planifier.
- Aucune suite de tests automatisés (PHPUnit) n'existe encore ; la vérification a été faite manuellement (`php -l`, `route:list`, relecture).
- MFA, chiffrement applicatif des données sensibles et sauvegardes automatiques ne sont pas couverts par cette implémentation.
- Envoi d'email synchrone (pas de queue) — acceptable au volume actuel, à reconsidérer si le nombre de comptes créés simultanément augmente significativement.

---

## 11. Ressources Laravel

Documentation générale du framework : [laravel.com/docs](https://laravel.com/docs). Ce projet est basé sur le squelette standard `laravel/laravel` ; voir `composer.json` pour la liste complète des dépendances.
