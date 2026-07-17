<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Soutenance;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Espace self-service jury. La saisie des notes (grille d'évaluation) passe
 * par SoutenanceNoteController::store (schéma critere/note/commentaire),
 * désormais gardé par SoutenancePolicy::noter (accès actif + notes non
 * verrouillées). Ce contrôleur gère uniquement la consultation et le
 * verrouillage définitif des notes.
 */
class JuryController extends Controller
{
    public function mesSoutenances(Request $request)
    {
        $user = $request->user();

        if (!$user->hasRole('jury_soutenance')) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $soutenances = Soutenance::whereHas('jury', function ($query) use ($user) {
            $query->where('user_id', $user->id)->where('actif', true);
        })
        ->with(['memoire.etudiant.user', 'memoire.versions', 'jury' => function ($query) use ($user) {
            $query->where('user_id', $user->id);
        }])
        ->where('statut', '!=', 'annulee')
        ->get();

        return response()->json($soutenances);
    }

    public function informationsSoutenance(Request $request, Soutenance $soutenance)
    {
        $user = $request->user();

        if (!$soutenance->estMembreDuJury($user)) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $juryMember = $soutenance->jury()->where('user_id', $user->id)->first();

        if (!$juryMember || !$juryMember->estActif()) {
            return response()->json(['message' => 'Votre accès à cette soutenance a expiré'], 403);
        }

        return response()->json($soutenance->load([
            'memoire.etudiant.user',
            'memoire.versions',
            'jury.membre',
            'notes'
        ]));
    }

    public function consulterMemoire(Request $request, Soutenance $soutenance)
    {
        $user = $request->user();

        if (!$soutenance->estMembreDuJury($user)) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $juryMember = $soutenance->jury()->where('user_id', $user->id)->first();

        if (!$juryMember || !$juryMember->estActif()) {
            return response()->json(['message' => 'Votre accès a expiré'], 403);
        }

        $memoire = $soutenance->memoire->load(['etudiant.user', 'versions', 'encadreur']);

        return response()->json($memoire);
    }

    public function telechargerMemoire(Request $request, Soutenance $soutenance)
    {
        $user = $request->user();

        if (!$soutenance->estMembreDuJury($user)) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $juryMember = $soutenance->jury()->where('user_id', $user->id)->first();

        if (!$juryMember || !$juryMember->estActif()) {
            return response()->json(['message' => 'Votre accès a expiré'], 403);
        }

        $derniereVersion = $soutenance->memoire->derniereVersion;

        if (!$derniereVersion || !$derniereVersion->fichier_path) {
            return response()->json(['message' => 'Aucun fichier disponible'], 404);
        }

        if (!Storage::disk('local')->exists($derniereVersion->fichier_path)) {
            return response()->json(['message' => 'Fichier non trouvé'], 404);
        }

        return Storage::disk('local')->download(
            $derniereVersion->fichier_path,
            $derniereVersion->fichier_nom_original ?: 'memoire.pdf'
        );
    }

    /**
     * Verrouillage définitif des notes du juré courant pour cette soutenance.
     * Une fois verrouillées, elles ne sont plus modifiables.
     */
    public function validerMesNotes(Request $request, Soutenance $soutenance)
    {
        $this->authorize('validerNotesJury', $soutenance);

        $juryRow = $soutenance->jury()->where('user_id', $request->user()->id)->first();
        $juryRow->validerNotes();

        if ($soutenance->tousLesJuryOntNote()) {
            app(NotificationService::class)->tousJuryOntNote($soutenance);
        }

        return response()->json($juryRow);
    }

    public function verifierAcces(Request $request, Soutenance $soutenance)
    {
        $user = $request->user();

        if (!$user->hasRole('jury_soutenance')) {
            return response()->json(['acces_autorise' => false, 'raison' => 'Pas membre du jury']);
        }

        $juryMember = $soutenance->jury()->where('user_id', $user->id)->first();

        if (!$juryMember) {
            return response()->json(['acces_autorise' => false, 'raison' => 'Pas assigné à cette soutenance']);
        }

        if (!$juryMember->estActif()) {
            return response()->json(['acces_autorise' => false, 'raison' => 'Période d\'accès expirée']);
        }

        if ($soutenance->estTerminee()) {
            return response()->json(['acces_autorise' => false, 'raison' => 'Soutenance terminée']);
        }

        return response()->json([
            'acces_autorise' => true,
            'date_fin_acces' => $juryMember->date_fin_acces,
            'soutenance' => collect($soutenance)->only(['id', 'date_soutenance', 'heure_debut', 'salle']),
        ]);
    }
}
