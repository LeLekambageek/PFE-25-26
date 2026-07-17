<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RapportStage;
use App\Models\Stage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RapportStageController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', RapportStage::class);

        $user = $request->user();
        $query = RapportStage::with(['stage.etudiant.user', 'stage.entreprise']);

        if ($user->hasRole('etudiant')) {
            $query->where('etudiant_id', $user->etudiant->id);
        } elseif ($user->hasRole('enseignant_encadreur')) {
            $query->whereHas('stage', function ($q) use ($user) {
                $q->where('encadreur_id', $user->enseignant->id);
            });
        }

        return response()->json($query->latest()->paginate(15));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'stage_id' => 'required|exists:stages,id',
            'type_rapport' => 'required|in:intermediaire,final',
            'fichier' => 'required|file|mimes:pdf,doc,docx|max:10240',
        ]);

        $stage = Stage::findOrFail($data['stage_id']);

        $this->authorize('create', [RapportStage::class, $stage]);

        $etudiant = $request->user()->etudiant;

        $existingRapport = RapportStage::where('stage_id', $stage->id)
            ->where('type_rapport', $data['type_rapport'])
            ->first();

        if ($existingRapport && !$existingRapport->peutEtreModifie()) {
            return response()->json([
                'message' => 'Ce rapport ne peut plus être modifié.'
            ], 422);
        }

        $filePath = $request->file('fichier')->store('rapports_stage', 'public');

        $rapport = RapportStage::updateOrCreate(
            [
                'stage_id' => $stage->id,
                'type_rapport' => $data['type_rapport'],
            ],
            [
                'etudiant_id' => $etudiant->id,
                'fichier_path' => $filePath,
                'fichier_nom_original' => $request->file('fichier')->getClientOriginalName(),
                'statut' => 'soumis',
                'date_soumission' => now(),
            ]
        );

        return response()->json($rapport->load(['stage.etudiant.user', 'stage.entreprise']), 201);
    }

    public function show(Request $request, RapportStage $rapport)
    {
        $this->authorize('view', $rapport);

        return response()->json($rapport->load(['stage.etudiant.user', 'stage.entreprise', 'stage.encadreur.user']));
    }

    public function corriger(Request $request, RapportStage $rapport)
    {
        $this->authorize('corriger', $rapport);

        $data = $request->validate([
            'commentaire_encadreur' => 'required|string',
        ]);

        $rapport->update([
            'statut' => 'corrige',
            'commentaire_encadreur' => $data['commentaire_encadreur'],
            'date_correction' => now(),
        ]);

        return response()->json($rapport->load(['stage.etudiant.user', 'stage.entreprise']));
    }

    public function valider(Request $request, RapportStage $rapport)
    {
        $this->authorize('valider', $rapport);

        $rapport->update([
            'statut' => 'valide',
            'date_correction' => now(),
        ]);

        return response()->json($rapport->load(['stage.etudiant.user', 'stage.entreprise']));
    }

    public function download(Request $request, RapportStage $rapport)
    {
        $this->authorize('view', $rapport);

        if (!Storage::disk('public')->exists($rapport->fichier_path)) {
            return response()->json(['message' => 'Fichier non trouvé'], 404);
        }

        return Storage::disk('public')->download($rapport->fichier_path, $rapport->fichier_nom_original);
    }

    public function destroy(Request $request, RapportStage $rapport)
    {
        $this->authorize('delete', $rapport);

        if (!$rapport->peutEtreModifie()) {
            return response()->json([
                'message' => 'Ce rapport ne peut plus être supprimé.'
            ], 422);
        }

        if ($rapport->fichier_path) {
            Storage::disk('public')->delete($rapport->fichier_path);
        }

        $rapport->delete();

        return response()->json(null, 204);
    }
}
