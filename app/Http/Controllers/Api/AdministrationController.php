<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enseignant;
use App\Models\Etudiant;
use App\Models\Memoire;
use App\Models\SoutenanceJury;
use App\Models\Stage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;


class AdministrationController extends Controller
{
    public function creerCompteEtudiant(Request $request)
    {
        $this->authorize('createUser', User::class);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'matricule' => 'required|string|unique:etudiants,matricule',
            'filiere' => 'required|string',
            'niveau' => 'required|string',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $user->assignRole('etudiant');

        $etudiant = Etudiant::create([
            'user_id' => $user->id,
            'matricule' => $data['matricule'],
            'filiere' => $data['filiere'],
            'niveau' => $data['niveau'],
        ]);

        return response()->json($etudiant->load('user'), 201);
    }

    public function gererComptesEnseignants(Request $request)
    {
        $this->authorize('manageUsers', User::class);

        return response()->json(Enseignant::with('user')->paginate(20));
    }

    public function creerCompteEnseignant(Request $request)
    {
        $this->authorize('createUser', User::class);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'specialite' => 'nullable|string',
            'capacite_encadrement' => 'integer|min:1|max:20',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $user->assignRole('enseignant_encadreur');

        $enseignant = Enseignant::create([
            'user_id' => $user->id,
            'specialite' => $data['specialite'] ?? null,
            'capacite_encadrement' => $data['capacite_encadrement'] ?? 5,
        ]);

        return response()->json($enseignant->load('user'), 201);
    }

    public function attribuerRoleEncadreur(Request $request, $enseignantId)
    {
        $this->authorize('assignRole', User::class);

        $enseignant = Enseignant::findOrFail($enseignantId);
        $enseignant->user->assignRole('enseignant_encadreur');

        return response()->json(['message' => "Rôle d'encadreur attribué avec succès"]);
    }

    
    public function associerEntrepriseEtudiant(Request $request, $etudiantId)
    {
        $this->authorize('associerEntreprise', Stage::class);

        $data = $request->validate([
            'entreprise_id' => 'required|exists:entreprises,id',
        ]);

        $stage = Stage::where('etudiant_id', $etudiantId)
            ->whereIn('statut', ['valide', 'en_cours'])
            ->latest()
            ->first();

        if (! $stage) {
            return response()->json(['message' => "Aucun stage en cours pour cet étudiant"], 404);
        }

        $stage->update(['entreprise_id' => $data['entreprise_id']]);

        return response()->json($stage->load('entreprise'));
    }

    
    public function memoiresValidesFinale(Request $request)
    {
        if (! $request->user()->hasAnyRole(['admin_general', 'responsable_formation'])) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $memoires = Memoire::with(['etudiant', 'encadreur', 'derniereVersion'])
            ->where('statut', 'valide_final')
            ->whereDoesntHave('soutenance')
            ->latest()
            ->paginate(20);

        return response()->json($memoires);
    }

    public function gererComptesJury(Request $request)
    {
        $this->authorize('manageJury', User::class);

        $query = User::role('jury_soutenance')->with('soutenanceJury.soutenance.memoire.etudiant');

        return response()->json($query->paginate(20));
    }

    
    public function creerCompteJury(Request $request)
    {
        $this->authorize('createUser', User::class);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $user->assignRole('jury_soutenance');

        return response()->json($user, 201);
    }

    /**
     * Réactivation d'un membre du jury pour une autre soutenance (nouvelle
     * fenêtre d'accès).
     */
    public function reactiverJury(Request $request, SoutenanceJury $jury)
    {
        if (! $request->user()->hasAnyRole(['admin_general', 'responsable_formation'])) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $jury->reactiver();

        return response()->json($jury->load('membre'));
    }
}
