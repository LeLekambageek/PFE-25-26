<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\UserCredentialsMail;
use App\Models\Enseignant;
use App\Models\Etudiant;
use App\Models\Memoire;
use App\Models\SoutenanceJury;
use App\Models\Stage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AdministrationController extends Controller
{

    private function genererEtEnvoyerIdentifiants(User $user, string $motDePasse): void
    {
        $user->update([
            'password' => Hash::make($motDePasse),
            'must_change_password' => true,
        ]);

        Mail::to($user->email)->send(new UserCredentialsMail($user->name, $user->email, $motDePasse));
    }

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

        $this->genererEtEnvoyerIdentifiants($user, $data['password']);

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

        $this->genererEtEnvoyerIdentifiants($user, $data['password']);

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
        if (! $request->user()->hasRole('administration')) {
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
            'date_debut_acces' => 'nullable|date',
            'date_fin_acces' => 'nullable|date|after_or_equal:date_debut_acces',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'date_debut_acces' => $data['date_debut_acces'] ?? null,
            'date_expiration' => $data['date_fin_acces'] ?? null,
        ]);

        $user->assignRole('jury_soutenance');

        $this->genererEtEnvoyerIdentifiants($user, $data['password']);

        return response()->json($user, 201);
    }


    public function reactiverJury(Request $request, SoutenanceJury $jury)
    {
        if (! $request->user()->hasRole('administration')) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $data = $request->validate([
            'date_debut_acces' => 'nullable|date',
            'date_fin_acces' => 'nullable|date|after_or_equal:date_debut_acces',
        ]);

        $jury->reactiver();

        if (isset($data['date_debut_acces']) || isset($data['date_fin_acces'])) {
            $jury->membre->update([
                'date_debut_acces' => $data['date_debut_acces'] ?? null,
                'date_expiration' => $data['date_fin_acces'] ?? null,
            ]);
        }

        return response()->json($jury->load('membre'));
    }


    public function reinitialiserMotDePasse(Request $request, User $user)
    {
        $this->authorize('manageUsers', User::class);

        $data = $request->validate([
            'password' => 'required|string|min:8',
        ]);

        $this->genererEtEnvoyerIdentifiants($user, $data['password']);

        return response()->json(['message' => 'Mot de passe réinitialisé, les nouveaux identifiants ont été envoyés par email.']);
    }

    public function modifierCompteEtudiant(Request $request, $id)
    {
        $this->authorize('updateUser', User::class);

        $etudiant = Etudiant::findOrFail($id);
        $user = $etudiant->user;

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8',
            'matricule' => 'required|string|unique:etudiants,matricule,' . $etudiant->id,
            'filiere' => 'required|string',
            'niveau' => 'required|string',
        ]);

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        if (!empty($data['password'])) {
            $this->genererEtEnvoyerIdentifiants($user, $data['password']);
        }

        $etudiant->update([
            'matricule' => $data['matricule'],
            'filiere' => $data['filiere'],
            'niveau' => $data['niveau'],
        ]);

        return response()->json($etudiant->load('user'));
    }

    public function supprimerCompteEtudiant($id)
    {
        $this->authorize('deleteUser', User::class);

        $etudiant = Etudiant::findOrFail($id);
        $user = $etudiant->user;

        // Cascade delete will delete profile, but we delete user to trigger cascade
        $user->delete();

        return response()->json(null, 204);
    }

    public function modifierCompteEnseignant(Request $request, $id)
    {
        $this->authorize('updateUser', User::class);

        $enseignant = Enseignant::findOrFail($id);
        $user = $enseignant->user;

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8',
            'specialite' => 'nullable|string',
            'capacite_encadrement' => 'required|integer|min:1|max:20',
        ]);

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        if (!empty($data['password'])) {
            $this->genererEtEnvoyerIdentifiants($user, $data['password']);
        }

        $enseignant->update([
            'specialite' => $data['specialite'] ?? null,
            'capacite_encadrement' => $data['capacite_encadrement'],
        ]);

        return response()->json($enseignant->load('user'));
    }

    public function supprimerCompteEnseignant($id)
    {
        $this->authorize('deleteUser', User::class);

        $enseignant = Enseignant::findOrFail($id);
        $user = $enseignant->user;

        $user->delete();

        return response()->json(null, 204);
    }

    public function modifierCompteJury(Request $request, $id)
    {
        $this->authorize('updateUser', User::class);

        $user = User::findOrFail($id);

        if (!$user->hasRole('jury_soutenance')) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8',
            'date_debut_acces' => 'nullable|date',
            'date_fin_acces' => 'nullable|date|after_or_equal:date_debut_acces',
        ]);

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'date_debut_acces' => $data['date_debut_acces'] ?? null,
            'date_expiration' => $data['date_fin_acces'] ?? null,
        ]);

        if (!empty($data['password'])) {
            $this->genererEtEnvoyerIdentifiants($user, $data['password']);
        }

        return response()->json($user);
    }

    public function supprimerCompteJury($id)
    {
        $this->authorize('deleteUser', User::class);

        $user = User::findOrFail($id);

        if (!$user->hasRole('jury_soutenance')) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $user->delete();

        return response()->json(null, 204);
    }
}