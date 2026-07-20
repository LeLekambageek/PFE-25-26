<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CandidatureStage;
use App\Models\OffreStage;
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
        $query = CandidatureStage::with(['etudiant.user', 'entreprise', 'offre']);

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
            'offre_id' => 'nullable|exists:offres_stage,id',
            'entreprise_id' => 'nullable|exists:entreprises,id',
            'titre_poste' => 'required_without:offre_id|string|max:255',
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

        $offre = null;
        if (!empty($data['offre_id'])) {
            $offre = OffreStage::findOrFail($data['offre_id']);
            if (!$offre->estOuverte()) {
                return response()->json([
                    'message' => 'Cette offre de stage n\'est plus ouverte aux candidatures.'
                ], 422);
            }
        }

        $cvPath = $request->file('cv')->store('cvs', 'public');
        $lettrePath = $request->file('lettre_motivation')->store('lettres_motivation', 'public');

        $candidature = CandidatureStage::create([
            'etudiant_id' => $etudiant->id,
            'offre_id' => $offre?->id,
            'entreprise_id' => $offre?->entreprise_id ?? $data['entreprise_id'] ?? null,
            'titre_poste' => $offre?->titre ?? $data['titre_poste'],
            'description' => $offre?->description ?? $data['description'] ?? null,
            'cv_path' => $cvPath,
            'lettre_motivation_path' => $lettrePath,
            'statut' => 'en_attente',
            'date_candidature' => now(),
        ]);

        app(NotificationService::class)->nouvelleCandidatureStage($candidature);

        return response()->json($candidature->load(['etudiant.user', 'entreprise', 'offre']), 201);
    }

    public function show(Request $request, CandidatureStage $candidature)
    {
        $this->authorize('view', $candidature);

        return response()->json($candidature->load(['etudiant.user', 'entreprise', 'offre', 'stage']));
    }

    public function update(Request $request, CandidatureStage $candidature)
    {
        $this->authorize('update', $candidature);

        $data = $request->validate([
            'statut' => 'required|in:en_attente,retenue,rejetee,stage_affecte',
            'commentaire_admin' => 'nullable|string',
            'stage_id' => 'nullable|exists:stages,id',
        ]);

        $statutPrecedent = $candidature->statut;

        $candidature->update([
            'statut' => $data['statut'],
            'commentaire_admin' => $data['commentaire_admin'] ?? null,
            'stage_id' => $data['stage_id'] ?? null,
            'date_reponse' => now(),
        ]);

        // Convocation de l'étudiant dès que sa candidature est retenue.
        if ($data['statut'] === 'retenue' && $statutPrecedent !== 'retenue') {
            app(NotificationService::class)->envoyerNotification(
                $candidature->etudiant->user,
                'candidature_retenue',
                'Candidature retenue',
                "Votre candidature pour le poste '{$candidature->titre_poste}' a été retenue.",
                $candidature,
                ['candidature_id' => $candidature->id]
            );
        }

        return response()->json($candidature->load(['etudiant.user', 'entreprise', 'offre', 'stage']));
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

        // Deux actions successives et distinctes : affectation du stage d'abord,
        // affectation de l'encadreur ensuite (via StageController::affecterEncadreur).
        // Pas d'encadreur_id ici, volontairement.
        $data = $request->validate([
            'entreprise_id' => 'required|exists:entreprises,id',
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after:date_debut',
        ]);

        $stage = Stage::create([
            'etudiant_id' => $candidature->etudiant_id,
            'entreprise_id' => $data['entreprise_id'],
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

        app(NotificationService::class)->affectationStage($stage);

        return response()->json($stage->load(['etudiant.user', 'entreprise']), 201);
    }
}