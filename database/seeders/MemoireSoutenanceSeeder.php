<?php

namespace Database\Seeders;

use App\Models\Memoire;
use App\Models\MemoireVersion;
use App\Models\Soutenance;
use App\Models\SoutenanceJury;
use App\Models\SoutenanceNote;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MemoireSoutenanceSeeder extends Seeder
{
    /**
     * Mot de passe commun à tous les comptes de test : password
     */
    public function run(): void
    {
        // --- Utilisateurs de test (un par rôle) ---

        $admin = User::firstOrCreate(
            ['email' => 'admin@epfafrica.test'],
            ['name' => 'Amath Admin', 'password' => Hash::make('password')]
        );
        $admin->syncRoles(['admin_general']);

        $responsable = User::firstOrCreate(
            ['email' => 'responsable@epfafrica.test'],
            ['name' => 'Fatou Responsable', 'password' => Hash::make('password')]
        );
        $responsable->syncRoles(['responsable_formation']);

        $encadreurs = collect(['Moussa Diop', 'Aissatou Ba', 'Ibrahima Fall'])->map(function ($nom, $i) {
            $user = User::firstOrCreate(
                ['email' => 'encadreur'.($i + 1).'@epfafrica.test'],
                ['name' => $nom, 'password' => Hash::make('password')]
            );
            $user->syncRoles(['enseignant_encadreur']);

            return $user;
        });

        $jurys = collect(['Cheikh Ndiaye', 'Awa Sarr', 'Modou Gueye'])->map(function ($nom, $i) {
            $user = User::firstOrCreate(
                ['email' => 'jury'.($i + 1).'@epfafrica.test'],
                ['name' => $nom, 'password' => Hash::make('password')]
            );
            $user->syncRoles(['jury_soutenance']);

            return $user;
        });

        $etudiants = collect(['Sayba Cissokho', 'Mariama Diallo', 'Ousmane Sy', 'Bineta Fall'])->map(function ($nom, $i) {
            $user = User::firstOrCreate(
                ['email' => 'etudiant'.($i + 1).'@epfafrica.test'],
                ['name' => $nom, 'password' => Hash::make('password')]
            );
            $user->syncRoles(['etudiant']);

            return $user;
        });

        // --- Mémoires à différents stades ---

        $memoire1 = Memoire::create([
            'titre' => 'Plateforme de gestion académique pour EPF Africa',
            'description' => 'Conception et développement d\'une plateforme numérique.',
            'etudiant_id' => $etudiants[0]->id,
            'encadreur_id' => $encadreurs[0]->id,
            'propose_par_id' => $etudiants[0]->id,
            'statut' => 'soutenu',
            'date_proposition' => now()->subMonths(6),
            'date_validation' => now()->subMonths(5)->subDays(20),
        ]);

        $memoire2 = Memoire::create([
            'titre' => 'Système de recommandation pour bibliothèque numérique',
            'description' => 'Étude et implémentation d\'un moteur de recherche multicritère.',
            'etudiant_id' => $etudiants[1]->id,
            'encadreur_id' => $encadreurs[1]->id,
            'propose_par_id' => $etudiants[1]->id,
            'statut' => 'valide_final',
            'date_proposition' => now()->subMonths(4),
            'date_validation' => now()->subMonths(3)->subDays(15),
        ]);

        $memoire3 = Memoire::create([
            'titre' => 'Analyse de données pour le pilotage académique',
            'description' => 'Tableaux de bord décisionnels et indicateurs de performance.',
            'etudiant_id' => $etudiants[2]->id,
            'encadreur_id' => $encadreurs[2]->id,
            'propose_par_id' => $etudiants[2]->id,
            'statut' => 'corrections_demandees',
            'date_proposition' => now()->subMonths(2),
            'date_validation' => now()->subMonths(1)->subDays(20),
        ]);

        Memoire::create([
            'titre' => 'Sécurisation des échanges dans une application académique',
            'description' => null,
            'etudiant_id' => $etudiants[3]->id,
            'encadreur_id' => null,
            'propose_par_id' => $etudiants[3]->id,
            'statut' => 'propose',
            'date_proposition' => now()->subDays(5),
        ]);

        // --- Versions déposées pour memoire2 et memoire3 ---

        MemoireVersion::create([
            'memoire_id' => $memoire2->id,
            'soumis_par_id' => $etudiants[1]->id,
            'numero_version' => 'v1',
            'fichier_path' => 'memoires/seed/memoire2_v1.pdf',
            'fichier_nom_original' => 'memoire2_v1.pdf',
            'statut' => 'valide',
        ]);
        MemoireVersion::create([
            'memoire_id' => $memoire2->id,
            'soumis_par_id' => $etudiants[1]->id,
            'numero_version' => 'finale',
            'fichier_path' => 'memoires/seed/memoire2_finale.pdf',
            'fichier_nom_original' => 'memoire2_finale.pdf',
            'statut' => 'valide',
        ]);

        $v1Memoire3 = MemoireVersion::create([
            'memoire_id' => $memoire3->id,
            'soumis_par_id' => $etudiants[2]->id,
            'numero_version' => 'v1',
            'fichier_path' => 'memoires/seed/memoire3_v1.pdf',
            'fichier_nom_original' => 'memoire3_v1.pdf',
            'statut' => 'corrige',
        ]);
        $v1Memoire3->corrections()->create([
            'auteur_id' => $encadreurs[2]->id,
            'commentaire' => 'Revoir la partie méthodologie et étoffer la bibliographie.',
            'type_correction' => 'annotation',
        ]);

        // --- Soutenance terminée avec jury, notes et résultats publiés (memoire1) ---

        $soutenance1 = Soutenance::create([
            'memoire_id' => $memoire1->id,
            'planifiee_par_id' => $responsable->id,
            'date_soutenance' => now()->subMonth(),
            'heure_debut' => '09:00',
            'salle' => 'Amphi A',
            'statut' => 'terminee',
            'resultats_publies' => true,
        ]);

        SoutenanceJury::create(['soutenance_id' => $soutenance1->id, 'user_id' => $jurys[0]->id, 'role_jury' => 'president']);
        SoutenanceJury::create(['soutenance_id' => $soutenance1->id, 'user_id' => $jurys[1]->id, 'role_jury' => 'rapporteur']);
        SoutenanceJury::create(['soutenance_id' => $soutenance1->id, 'user_id' => $jurys[2]->id, 'role_jury' => 'examinateur']);

        foreach ($jurys as $membre) {
            SoutenanceNote::create([
                'soutenance_id' => $soutenance1->id,
                'jury_id' => $membre->id,
                'critere' => 'fond',
                'note' => 16,
            ]);
            SoutenanceNote::create([
                'soutenance_id' => $soutenance1->id,
                'jury_id' => $membre->id,
                'critere' => 'forme',
                'note' => 15,
            ]);
        }

        $soutenance1->update([
            'note_finale' => 15.5,
            'mention' => 'Bien',
        ]);

        // --- Soutenance planifiée à venir pour memoire2 ---

        $soutenance2 = Soutenance::create([
            'memoire_id' => $memoire2->id,
            'planifiee_par_id' => $responsable->id,
            'date_soutenance' => now()->addWeeks(2),
            'heure_debut' => '14:00',
            'salle' => 'Salle B12',
            'statut' => 'planifiee',
        ]);

        SoutenanceJury::create(['soutenance_id' => $soutenance2->id, 'user_id' => $jurys[0]->id, 'role_jury' => 'president']);
        SoutenanceJury::create(['soutenance_id' => $soutenance2->id, 'user_id' => $jurys[1]->id, 'role_jury' => 'rapporteur']);

        $this->command->info('Seeder Mémoires/Soutenances exécuté : comptes de test créés (mot de passe : password).');
    }
}
