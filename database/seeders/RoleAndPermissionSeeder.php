<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'stages.demander', 'stages.valider', 'stages.affecter_encadreur',
            'stages.suivre', 'stages.evaluer',

            'encadrements.gerer', 'encadrements.consulter',

            'memoires.proposer', 'memoires.valider', 'memoires.deposer_version', 'memoires.corriger',

            'soutenances.planifier', 'soutenances.noter', 'soutenances.publier_resultats',

            'bibliotheque.consulter', 'bibliotheque.archiver',
            'bibliotheque.supprimer', 'bibliotheque.modifier_metadonnees',

            'entreprises.consulter', 'entreprises.ajouter', 'entreprises.modifier', 'entreprises.supprimer',

            'dashboards.consulter',

            'utilisateurs.consulter', 'utilisateurs.creer', 'utilisateurs.modifier',
            'utilisateurs.desactiver', 'utilisateurs.supprimer',
            'utilisateurs.attribuer_role', 'utilisateurs.retirer_role',
            'parametres.gerer',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $admin = Role::firstOrCreate(['name' => 'admin_general']);
        $admin->givePermissionTo(Permission::all());

        $responsableFormation = Role::firstOrCreate(['name' => 'responsable_formation']);
        $responsableFormation->givePermissionTo([
            'stages.suivre', 'stages.valider', 'stages.affecter_encadreur',
            'encadrements.gerer', 'encadrements.consulter',
            'memoires.valider',
            'soutenances.planifier', 'soutenances.publier_resultats',
            'dashboards.consulter',
            'bibliotheque.consulter', 'bibliotheque.archiver',
            'entreprises.consulter',
        ]);

        $encadreur = Role::firstOrCreate(['name' => 'enseignant_encadreur']);
        $encadreur->givePermissionTo([
            'stages.suivre', 'stages.valider', 'stages.evaluer',
            'encadrements.consulter',
            'memoires.corriger',
            'bibliotheque.consulter',
            'dashboards.consulter',
        ]);

        $etudiant = Role::firstOrCreate(['name' => 'etudiant']);
        $etudiant->givePermissionTo([
            'stages.demander', 'stages.suivre',
            'memoires.proposer', 'memoires.deposer_version',
            'encadrements.consulter',
            'bibliotheque.consulter',
        ]);

        $entreprise = Role::firstOrCreate(['name' => 'entreprise_partenaire']);
        $entreprise->givePermissionTo(['stages.evaluer']);

        $jury = Role::firstOrCreate(['name' => 'jury_soutenance']);
        $jury->givePermissionTo(['soutenances.noter']);
    }
}