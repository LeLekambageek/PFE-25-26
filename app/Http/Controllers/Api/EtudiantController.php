<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Etudiant;
use Illuminate\Http\Request;

/**
 * Espace self-service étudiant. La proposition de sujet de mémoire passe par
 * MemoireController::store, la réservation d'un créneau de soutenance par
 * CreneauSoutenanceController::reserver.
 */
class EtudiantController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Etudiant::class);

        $query = Etudiant::with(['user', 'stages', 'memoires']);

        if ($request->has('filiere')) {
            $query->where('filiere', $request->filiere);
        }

        if ($request->has('niveau')) {
            $query->where('niveau', $request->niveau);
        }

        return response()->json($query->paginate(20));
    }

    public function show(Request $request, Etudiant $etudiant)
    {
        $this->authorize('view', $etudiant);

        return response()->json($etudiant->load([
            'user',
            'stages.entreprise',
            'stages.encadreur.user',
            'memoires.encadreur',
            'memoires.derniereVersion',
            'encadrements.enseignant.user',
            'candidatures.entreprise',
            'rapports.stage'
        ]));
    }

    public function mesStages(Request $request)
    {
        $etudiant = $request->user()->etudiant;
        $stages = $etudiant->stages()->with(['entreprise', 'encadreur.user', 'journalEntries.auteur'])->get();

        return response()->json($stages);
    }

    public function monStageActif(Request $request)
    {
        $etudiant = $request->user()->etudiant;
        $stage = $etudiant->stageActif();

        if (!$stage) {
            return response()->json(['message' => 'Aucun stage en cours'], 404);
        }

        return response()->json($stage->load(['entreprise', 'encadreur.user', 'journalEntries.auteur']));
    }

    public function mesMemoires(Request $request)
    {
        $etudiant = $request->user()->etudiant;
        $memoires = $etudiant->memoires()->with(['encadreur', 'derniereVersion', 'soutenance'])->get();

        return response()->json($memoires);
    }

    public function mesEncadrements(Request $request)
    {
        $etudiant = $request->user()->etudiant;
        $encadrements = $etudiant->encadrements()->with(['enseignant.user', 'rendezVous', 'entries.auteur'])->get();

        return response()->json($encadrements);
    }

    public function mesCandidatures(Request $request)
    {
        $etudiant = $request->user()->etudiant;
        $candidatures = $etudiant->candidatures()->with(['entreprise', 'stage'])->get();

        return response()->json($candidatures);
    }

    public function mesRapports(Request $request)
    {
        $etudiant = $request->user()->etudiant;
        $rapports = $etudiant->rapports()->with(['stage.entreprise', 'stage.encadreur.user'])->get();

        return response()->json($rapports);
    }

    public function informationsSoutenance(Request $request)
    {
        $etudiant = $request->user()->etudiant;
        $memoire = $etudiant->memoires()->whereHas('soutenance')->first();

        if (!$memoire || !$memoire->soutenance) {
            return response()->json(['message' => 'Aucune soutenance planifiée'], 404);
        }

        $soutenance = $memoire->soutenance->load(['jury.membre', 'memoire.etudiant.user']);

        $joursRestants = now()->diffInDays($soutenance->date_soutenance);
        $heuresRestantes = now()->diffInHours($soutenance->date_soutenance);

        return response()->json([
            'soutenance' => $soutenance,
            'jours_restants' => $joursRestants,
            'heures_restantes' => $heuresRestantes,
            'compte_a_rebours' => $soutenance->date_soutenance->toAtomString(),
        ]);
    }

    public function resultatsSoutenance(Request $request)
    {
        $etudiant = $request->user()->etudiant;
        $memoire = $etudiant->memoires()->whereHas('soutenance')->first();

        if (!$memoire || !$memoire->soutenance) {
            return response()->json(['message' => 'Aucune soutenance trouvée'], 404);
        }

        $soutenance = $memoire->soutenance;

        if (!$soutenance->resultatsSontPublies()) {
            return response()->json(['message' => 'Les résultats ne sont pas encore publiés'], 403);
        }

        return response()->json($soutenance->load(['notes', 'jury.membre', 'procesVerbaux']));
    }
}
