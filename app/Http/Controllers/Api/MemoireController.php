<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Encadrement;
use App\Models\Memoire;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class MemoireController extends Controller
{

    public function index(Request $request)
    {
        $this->authorize('viewAny', Memoire::class);
        $user = $request->user();

        $query = Memoire::with(['etudiant', 'encadreur', 'derniereVersion']);

        if ($user->hasRole('etudiant')) {
            $query->where('etudiant_id', $user->id);
        } elseif ($user->hasRole('enseignant_encadreur') && ! $user->hasRole('administration')) {
            $query->where('encadreur_id', $user->id);
        } elseif ($user->hasRole('administration')) {
            $query->whereIn('statut', ['valide', 'valide_final']);
        }

        return response()->json($query->latest()->paginate(15));
    }

    public function show(Request $request, Memoire $memoire)
    {
        $this->authorize('view', $memoire);

        return response()->json($memoire->load(['etudiant', 'encadreur', 'versions.corrections', 'soutenance']));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Memoire::class);

        $data = $request->validate([
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
            'etudiant_id' => 'required|exists:users,id',
        ]);

        $user = $request->user();

        // Cas 1 : l'étudiant propose son propre sujet. Il est soumis à validation
        // par son encadreur déjà assigné (s'il en a un).
        if ($user->hasRole('etudiant')) {
            $encadrement = Encadrement::where('etudiant_id', $user->etudiant?->id)
                ->where('statut', 'actif')
                ->whereIn('type', ['memoire', 'stage'])
                ->latest()
                ->first();

            $memoire = Memoire::create([
                'titre' => $data['titre'],
                'description' => $data['description'] ?? null,
                'etudiant_id' => $user->id,
                'encadreur_id' => $encadrement?->enseignant?->user_id,
                'propose_par_id' => $user->id,
                'statut' => 'propose',
                'date_proposition' => now(),
            ]);

            if ($memoire->encadreur) {
                app(NotificationService::class)->nouvellePropositionSujet($memoire);
            }

            return response()->json($memoire, 201);
        }

        $etudiantCible = User::find($data['etudiant_id']);
        if (! $etudiantCible || ! $etudiantCible->hasRole('etudiant')) {
            return response()->json(['message' => "L'utilisateur cible n'a pas le role etudiant."], 422);
        }

        // Cas 2 : l'encadreur propose directement un sujet à un étudiant : pas de
        // validation nécessaire, l'étudiant peut démarrer immédiatement.
        if ($user->hasRole('enseignant_encadreur')) {
            $memoire = Memoire::create([
                'titre' => $data['titre'],
                'description' => $data['description'] ?? null,
                'etudiant_id' => $etudiantCible->id,
                'encadreur_id' => $user->id,
                'propose_par_id' => $user->id,
                'statut' => 'valide',
                'date_proposition' => now(),
                'date_validation' => now(),
            ]);

            if ($etudiantCible->etudiant && $user->enseignant) {
                Encadrement::firstOrCreate([
                    'etudiant_id' => $etudiantCible->etudiant->id,
                    'enseignant_id' => $user->enseignant->id,
                    'type' => 'memoire',
                ], ['statut' => 'actif']);
            }

            return response()->json($memoire, 201);
        }

        $memoire = Memoire::create([
            'titre' => $data['titre'],
            'description' => $data['description'] ?? null,
            'etudiant_id' => $etudiantCible->id,
            'propose_par_id' => $user->id,
            'statut' => 'propose',
            'date_proposition' => now(),
        ]);

        return response()->json($memoire, 201);
    }


    public function valider(Request $request, Memoire $memoire)
    {
        $this->authorize('valider', $memoire);

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


    public function rejeter(Request $request, Memoire $memoire)
    {
        $this->authorize('valider', $memoire);

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
     * L'encadreur demande une modification sur un sujet proposé par l'étudiant.
     */
    public function demanderModification(Request $request, Memoire $memoire)
    {
        $this->authorize('valider', $memoire);

        $data = $request->validate([
            'commentaire_validation' => 'required|string',
        ]);

        $memoire->update([
            'statut' => 'corrections_demandees',
            'commentaire_validation' => $data['commentaire_validation'],
        ]);

        app(NotificationService::class)->sujetAModifier($memoire);

        return response()->json($memoire);
    }


    public function affecterEncadreur(Request $request, Memoire $memoire)
    {
        $this->authorize('affecterEncadreur', Memoire::class);

        $data = $request->validate([
            'encadreur_id' => 'required|exists:users,id',
        ]);

        $encadreur = User::findOrFail($data['encadreur_id']);
        if (! $encadreur->hasRole('enseignant_encadreur')) {
            return response()->json(['message' => "L'utilisateur choisi n'a pas le role enseignant encadreur."], 422);
        }

        $memoire->update([
            'encadreur_id' => $data['encadreur_id'],
            'statut' => 'en_cours',
        ]);

        return response()->json($memoire->load('encadreur'));
    }

    /**
     * Geste explicite et distinct de l'encadreur qui autorise l'étudiant à
     * demander un créneau de soutenance (indépendant du pourcentage d'avancement).
     */
    public function accorderEligibiliteSoutenance(Request $request, Memoire $memoire)
    {
        $this->authorize('accorderEligibiliteSoutenance', $memoire);

        $memoire->update([
            'eligible_soutenance' => true,
            'date_eligibilite_soutenance' => now(),
        ]);

        return response()->json($memoire);
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