<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Memoire;
use App\Models\User;
use Illuminate\Http\Request;

class MemoireController extends Controller
{
    /**
     * Liste des mémoires, filtrée selon le rôle de l'utilisateur connecté.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Memoire::class);
        $user = $request->user();

        $query = Memoire::with(['etudiant', 'encadreur', 'derniereVersion']);

        if ($user->hasRole('etudiant')) {
            $query->where('etudiant_id', $user->id);
        } elseif ($user->hasRole('enseignant_encadreur') && ! $user->hasAnyRole(['admin_general', 'responsable_formation'])) {
            $query->where('encadreur_id', $user->id);
        }
        // admin_general et responsable_formation voient tout

        return response()->json($query->latest()->paginate(15));
    }

    public function show(Request $request, Memoire $memoire)
    {
        $this->authorize('view', $memoire);

        return response()->json($memoire->load(['etudiant', 'encadreur', 'versions.corrections', 'soutenance']));
    }

    /**
     * Proposition d'un sujet de mémoire (étudiant ou enseignant).
     */
    public function store(Request $request)
    {
        $this->authorize('create', Memoire::class);

        $data = $request->validate([
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
            'etudiant_id' => 'required|exists:users,id',
        ]);

        $memoire = Memoire::create([
            ...$data,
            'propose_par_id' => $request->user()->id,
            'statut' => 'propose',
            'date_proposition' => now(),
        ]);

        return response()->json($memoire, 201);
    }

    /**
     * Validation pédagogique du sujet proposé.
     */
    public function valider(Request $request, Memoire $memoire)
    {
        $this->authorize('valider', Memoire::class);

        $data = $request->validate([
            'commentaire_validation' => 'nullable|string',
        ]);

        $memoire->update([
            'statut' => 'valide',
            'date_validation' => now(),
            'commentaire_validation' => $data['commentaire_validation'] ?? null,
        ]);

        return response()->json($memoire);
    }

    /**
     * Rejet du sujet proposé.
     */
    public function rejeter(Request $request, Memoire $memoire)
    {
        $this->authorize('valider', Memoire::class);

        $data = $request->validate([
            'commentaire_validation' => 'required|string',
        ]);

        $memoire->update([
            'statut' => 'rejete',
            'date_validation' => now(),
            'commentaire_validation' => $data['commentaire_validation'],
        ]);

        return response()->json($memoire);
    }

    /**
     * Affectation d'un encadreur selon compétences/disponibilités.
     */
    public function affecterEncadreur(Request $request, Memoire $memoire)
    {
        $this->authorize('affecterEncadreur', Memoire::class);

        $data = $request->validate([
            'encadreur_id' => 'required|exists:users,id',
        ]);

        $encadreur = User::findOrFail($data['encadreur_id']);
        if (! $encadreur->hasRole('enseignant_encadreur')) {
            return response()->json(['message' => "L'utilisateur choisi n'a pas le rôle enseignant encadreur."], 422);
        }

        $memoire->update([
            'encadreur_id' => $data['encadreur_id'],
            'statut' => 'en_cours',
        ]);

        return response()->json($memoire->load('encadreur'));
    }

    public function update(Request $request, Memoire $memoire)
    {
        $this->authorize('update', $memoire);

        $data = $request->validate([
            'titre' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
        ]);

        $memoire->update($data);

        return response()->json($memoire);
    }

    public function destroy(Request $request, Memoire $memoire)
    {
        $this->authorize('delete', $memoire);
        $memoire->delete();

        return response()->json(null, 204);
    }
}
