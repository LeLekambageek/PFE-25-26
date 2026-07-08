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

            'dashboards.consulter',
    
            'utilisateurs.gerer', 'parametres.gerer',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $admin = Role::firstOrCreate(['name' => 'admin_general']);
        $admin->givePermissionTo(Permission::all());

        $responsableFormation = Role::firstOrCreate(['name' => 'responsable_formation']);
        $responsableFormation->givePermissionTo([
            'stages.valider', 'stages.affecter_encadreur', 'memoires.valider',
            'soutenances.planifier', 'dashboards.consulter', 'bibliotheque.consulter',
        ]);

        $encadreur = Role::firstOrCreate(['name' => 'enseignant_encadreur']);
        $encadreur->givePermissionTo([
            'stages.valider', 'stages.suivre', 'stages.evaluer', 'encadrements.gerer', 'encadrements.consulter',
            'memoires.corriger', 'bibliotheque.consulter',
        ]);

        $etudiant = Role::firstOrCreate(['name' => 'etudiant']);
        $etudiant->givePermissionTo([
            'stages.demander', 'stages.suivre', 'memoires.proposer', 'memoires.deposer_version',
            'encadrements.consulter', 'bibliotheque.consulter',
]);

        $entreprise = Role::firstOrCreate(['name' => 'entreprise_partenaire']);
        $entreprise->givePermissionTo(['stages.evaluer']);

        $jury = Role::firstOrCreate(['name' => 'jury_soutenance']);
        $jury->givePermissionTo(['soutenances.noter']);
    }
}