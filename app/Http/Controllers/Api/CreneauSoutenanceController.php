<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CreneauSoutenance;
use App\Models\Memoire;
use App\Models\Soutenance;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class CreneauSoutenanceController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', CreneauSoutenance::class);

        $query = CreneauSoutenance::with(['planifiePar', 'soutenance.memoire', 'memoire']);

        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->has('date_debut') && $request->has('date_fin')) {
            $query->whereBetween('date_disponible', [$request->date_debut, $request->date_fin]);
        }

        return response()->json($query->orderBy('date_disponible')->orderBy('heure_debut')->paginate(20));
    }

    public function store(Request $request)
    {
        $this->authorize('create', CreneauSoutenance::class);

        $data = $request->validate([
            'date_disponible' => 'required|date|after:today',
            'heure_debut' => 'required|date_format:H:i',
            'heure_fin' => 'required|date_format:H:i|after:heure_debut',
            'salle' => 'required|string|max:255',
        ]);

        $creneau = CreneauSoutenance::create([
            'planifie_par_id' => $request->user()->id,
            'date_disponible' => $data['date_disponible'],
            'heure_debut' => $data['heure_debut'],
            'heure_fin' => $data['heure_fin'],
            'salle' => $data['salle'],
            'statut' => 'disponible',
        ]);

        return response()->json($creneau->load('planifiePar'), 201);
    }

    public function show(Request $request, CreneauSoutenance $creneau)
    {
        $this->authorize('view', $creneau);

        return response()->json($creneau->load(['planifiePar', 'soutenance.memoire', 'memoire']));
    }

    public function reserver(Request $request, CreneauSoutenance $creneau)
    {
        $this->authorize('reserver', CreneauSoutenance::class);

        $data = $request->validate([
            'memoire_id' => 'required|exists:memoires,id',
        ]);

        $memoire = Memoire::findOrFail($data['memoire_id']);
        $user = $request->user();

        if (!$user->hasRole('etudiant') || $memoire->etudiant_id !== $user->id) {
            return response()->json(['message' => 'Ce mémoire ne vous appartient pas'], 403);
        }

        if (!$memoire->peutDemanderSoutenance()) {
            return response()->json([
                'message' => 'Votre mémoire doit être validé à 80% au minimum pour demander une soutenance'
            ], 422);
        }

        if (!$creneau->peutEtreReserve()) {
            return response()->json([
                'message' => 'Ce créneau ne peut plus être réservé (moins de 5 jours avant)'
            ], 422);
        }

        $creneau->reserver($memoire->id);

        if ($memoire->encadreur) {
            app(NotificationService::class)->demandeCreneauSoutenance($creneau);
        }

        return response()->json($creneau->load(['planifiePar', 'memoire']));
    }

    /**
     * Validation par l'administration de la réservation d'un créneau par un
     * étudiant : crée la soutenance officielle à partir du créneau réservé.
     */
    public function validerReservation(Request $request, CreneauSoutenance $creneau)
    {
        $this->authorize('validerReservation', CreneauSoutenance::class);

        if (! $creneau->estReserve()) {
            return response()->json(['message' => "Ce créneau n'est pas réservé"], 422);
        }

        $memoire = $creneau->memoire;

        if ($memoire->soutenance) {
            return response()->json(['message' => 'Une soutenance est déjà planifiée pour ce mémoire'], 422);
        }

        $soutenance = Soutenance::create([
            'memoire_id' => $memoire->id,
            'planifiee_par_id' => $request->user()->id,
            'date_soutenance' => $creneau->date_disponible,
            'heure_debut' => $creneau->heure_debut,
            'salle' => $creneau->salle,
            'statut' => 'planifiee',
        ]);

        $creneau->update(['soutenance_id' => $soutenance->id]);

        app(NotificationService::class)->soutenanceValidee($soutenance);

        return response()->json($soutenance->load(['memoire.etudiant.user', 'planifieePar']));
    }

    public function annuler(Request $request, CreneauSoutenance $creneau)
    {
        $this->authorize('annuler', CreneauSoutenance::class);

        if ($creneau->soutenance && $creneau->soutenance->statut === 'planifiee') {
            return response()->json([
                'message' => 'Impossible d\'annuler un créneau déjà validé par l\'administration'
            ], 422);
        }

        $creneau->annuler();

        return response()->json($creneau->load(['planifiePar']));
    }

    public function update(Request $request, CreneauSoutenance $creneau)
    {
        $this->authorize('update', $creneau);

        $data = $request->validate([
            'date_disponible' => 'sometimes|date|after:today',
            'heure_debut' => 'sometimes|date_format:H:i',
            'heure_fin' => 'sometimes|date_format:H:i|after:heure_debut',
            'salle' => 'sometimes|string|max:255',
            'statut' => 'sometimes|in:disponible,reserve,annule',
        ]);

        $creneau->update($data);

        return response()->json($creneau->load(['planifiePar', 'soutenance.memoire', 'memoire']));
    }

    public function destroy(Request $request, CreneauSoutenance $creneau)
    {
        $this->authorize('delete', $creneau);

        if ($creneau->estReserve()) {
            return response()->json([
                'message' => 'Impossible de supprimer un créneau réservé'
            ], 422);
        }

        $creneau->delete();

        return response()->json(null, 204);
    }

    public function disponiblesPourEtudiant(Request $request)
    {
        $user = $request->user();

        if (!$user->hasRole('etudiant')) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $memoire = $user->memoires()->where('statut', 'valide_final')->first();

        if (!$memoire || !$memoire->peutDemanderSoutenance()) {
            return response()->json([
                'message' => 'Vous n\'avez pas de mémoire éligible pour une soutenance',
                'creneaux' => []
            ]);
        }

        $creneaux = CreneauSoutenance::where('statut', 'disponible')
            ->where('date_disponible', '>', now()->addDays(5))
            ->orderBy('date_disponible')
            ->orderBy('heure_debut')
            ->get();

        return response()->json($creneaux);
    }
}
