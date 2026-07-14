<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Stage;
use App\Services\StageService;
use Illuminate\Http\Request;

class StageController extends Controller
{
    public function __construct(private StageService $stageService) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Stage::class);

        $user = $request->user();
        $query = Stage::query()->with(['etudiant.user', 'entreprise', 'encadreur.user']);

        if ($user->hasRole('etudiant')) {
            $query->where('etudiant_id', $user->etudiant->id);
        } elseif ($user->hasRole('enseignant_encadreur')) {
            $query->where('encadreur_id', $user->enseignant->id);
        }

        return response()->json($query->paginate(20));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Stage::class);

        $data = $request->validate([
            'entreprise_id' => 'nullable|exists:entreprises,id',
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after:date_debut',
        ]);

        $stage = $this->stageService->creerDemande($data, $request->user()->etudiant->id);

        return response()->json($stage, 201);
    }

    public function validerStage(Request $request, Stage $stage)
    {
        $this->authorize('validate', $stage);
        $stage = $this->stageService->valider($stage);

        return response()->json($stage);
    }

    public function affecterEncadreur(Request $request, Stage $stage)
    {
        $this->authorize('affecterEncadreur', Stage::class);

        $data = $request->validate(['encadreur_id' => 'required|exists:enseignants,id']);
        $stage = $this->stageService->affecterEncadreur($stage, $data['encadreur_id']);

        return response()->json($stage);
    }

    public function ajouterEntreeJournal(Request $request, Stage $stage)
    {
        $this->authorize('view', $stage);

        $data = $request->validate(['contenu' => 'required|string']);
        $entry = $this->stageService->ajouterEntreeJournal($stage, $request->user()->id, $data['contenu']);

        return response()->json($entry->load('auteur'), 201);
    }

    public function journal(Request $request, Stage $stage)
    {
        $this->authorize('view', $stage);

        return response()->json($stage->journalEntries()->with('auteur')->latest()->get());
    }

    public function cloturer(Request $request, Stage $stage)
    {
        $this->authorize('cloturer', $stage);
        $stage = $this->stageService->cloturerEtGenererAttestation($stage);

        return response()->json($stage);
    }
}