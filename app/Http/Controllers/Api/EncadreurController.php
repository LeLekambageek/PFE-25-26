<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Encadrement;
use App\Models\Enseignant;
use Illuminate\Http\Request;

/**
 * Endpoints self-service propres à l'encadreur, sans équivalent ailleurs.
 *
 * Proposition/examen de sujet de mémoire : voir MemoireController (store/valider/
 * rejeter/demanderModification). Annotation, avancement, validation finale des
 * versions : voir MemoireVersionController (corriger/validerFinale). Rendez-vous
 * et messagerie avec l'étudiant : voir EncadrementController (planifierRdv/
 * ajouterEntree/entrees).
 */
class EncadreurController extends Controller
{
    public function mesEtudiants(Request $request)
    {
        $enseignant = $request->user()->enseignant;
        $etudiants = $enseignant->etudiantsEncadres();

        return response()->json($etudiants->load(['user', 'stages.entreprise', 'memoires.derniereVersion']));
    }

    public function informationsStageEtudiant(Request $request, $etudiantId)
    {
        $this->authorize('viewEtudiantStage', Enseignant::class);

        $enseignant = $request->user()->enseignant;
        $encadrement = Encadrement::where('enseignant_id', $enseignant->id)
            ->where('etudiant_id', $etudiantId)
            ->where('type', 'stage')
            ->first();

        if (!$encadrement) {
            return response()->json(['message' => 'Vous n\'encadrez pas cet étudiant'], 403);
        }

        $stage = $encadrement->etudiant->stageActif();

        if (!$stage) {
            return response()->json(['message' => 'Aucun stage en cours pour cet étudiant'], 404);
        }

        return response()->json($stage->load([
            'entreprise',
            'journalEntries.auteur',
            'rapports',
            'etudiant.user'
        ]));
    }
}
