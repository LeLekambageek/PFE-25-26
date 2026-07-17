<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CandidatureStage;
use App\Models\Stage;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CandidatureStageController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', CandidatureStage::class);

        $user = $request->user();
        $query = CandidatureStage::with(['etudiant.user', 'entreprise']);

        if ($user->hasRole('etudiant')) {
            $query->where('etudiant_id', $user->etudiant->id);
        }

        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        return response()->json($query->latest()->paginate(15));
    }

    public function store(Request $request)
    {
        $this->authorize('create', CandidatureStage::class);

        $data = $request->validate([
            'entreprise_id' => 'nullable|exists:entreprises,id',
            'titre_poste' => 'required|string|max:255',
            'description' => 'nullable|string',
            'cv' => 'required|file|mimes:pdf,doc,docx|max:5120',
            'lettre_motivation' => 'required|file|mimes:pdf,doc,docx|max:5120',
        ]);

        $etudiant = $request->user()->etudiant;

        if (!$etudiant->peutPostulerCandidature()) {
            return response()->json([
                'message' => 'Vous avez déjà un stage en cours.'
            ], 422);
        }

        $cvPath = $request->file('cv')->store('cvs', 'public');
        $lettrePath = $request->file('lettre_motivation')->store('lettres_motivation', 'public');

        $candidature = CandidatureStage::create([
            'etudiant_id' => $etudiant->id,
            'entreprise_id' => $data['entreprise_id'] ?? null,
            'titre_poste' => $data['titre_poste'],
            'description' => $data['description'] ?? null,
            'cv_path' => $cvPath,
            'lettre_motivation_path' => $lettrePath,
            'statut' => 'en_attente',
            'date_candidature' => now(),
        ]);

        app(NotificationService::class)->nouvelleCandidatureStage($candidature);

        return response()->json($candidature->load(['etudiant.user', 'entreprise']), 201);
    }

    public function show(Request $request, CandidatureStage $candidature)
    {
        $this->authorize('view', $candidature);

        return response()->json($candidature->load(['etudiant.user', 'entreprise', 'stage']));
    }

    public function update(Request $request, CandidatureStage $candidature)
    {
        $this->authorize('update', $candidature);

        $data = $request->validate([
            'statut' => 'required|in:en_attente,retenue,rejetee,stage_affecte',
            'commentaire_admin' => 'nullable|string',
            'stage_id' => 'nullable|exists:stages,id',
        ]);

        $candidature->update([
            'statut' => $data['statut'],
            'commentaire_admin' => $data['commentaire_admin'] ?? null,
            'stage_id' => $data['stage_id'] ?? null,
            'date_reponse' => now(),
        ]);

        return response()->json($candidature->load(['etudiant.user', 'entreprise', 'stage']));
    }

    public function destroy(Request $request, CandidatureStage $candidature)
    {
        $this->authorize('delete', $candidature);

        if ($candidature->cv_path) {
            Storage::disk('public')->delete($candidature->cv_path);
        }
        if ($candidature->lettre_motivation_path) {
            Storage::disk('public')->delete($candidature->lettre_motivation_path);
        }

        $candidature->delete();

        return response()->json(null, 204);
    }

    public function affecterStage(Request $request, CandidatureStage $candidature)
    {
        $this->authorize('affecterStage', CandidatureStage::class);

        if (!$candidature->estRetenue()) {
            return response()->json([
                'message' => 'Seules les candidatures retenues peuvent être affectées à un stage.'
            ], 422);
        }

        $data = $request->validate([
            'entreprise_id' => 'required|exists:entreprises,id',
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after:date_debut',
            'encadreur_id' => 'nullable|exists:enseignants,id',
        ]);

        $stage = Stage::create([
            'etudiant_id' => $candidature->etudiant_id,
            'entreprise_id' => $data['entreprise_id'],
            'encadreur_id' => $data['encadreur_id'] ?? null,
            'titre' => $data['titre'],
            'description' => $data['description'] ?? null,
            'date_debut' => $data['date_debut'],
            'date_fin' => $data['date_fin'],
            'statut' => 'valide',
        ]);

        $candidature->update([
            'statut' => 'stage_affecte',
            'stage_id' => $stage->id,
            'date_reponse' => now(),
        ]);

        return response()->json($stage->load(['etudiant.user', 'entreprise', 'encadreur.user']), 201);
    }
}
