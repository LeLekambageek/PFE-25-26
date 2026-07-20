<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProcesVerbal;
use App\Models\Soutenance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProcesVerbalController extends Controller
{
    /**
     * Liste des procès-verbaux d'une soutenance.
     */
    public function index(Request $request, Soutenance $soutenance)
    {
        $this->authorize('view', $soutenance);

        $pvs = $soutenance->procesVerbaux()
            ->with('generePar')
            ->orderBy('date_generation', 'desc')
            ->get();

        return response()->json(['success' => true, 'data' => $pvs]);
    }

    /**
     * Générer un procès-verbal (après notation par le jury).
     */
    public function generer(Request $request, Soutenance $soutenance)
    {
        $this->authorize('generer', [ProcesVerbal::class, $soutenance]);

        $soutenance->load(['memoire.etudiant', 'jury.membre', 'notes']);

        if ($soutenance->notes->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'La soutenance doit être notée avant de générer un PV.',
            ], 422);
        }

        $request->validate([
            'commentaires' => 'nullable|string|max:1000',
        ]);

        $data = [
            'soutenance' => $soutenance,
            'etudiant' => $soutenance->memoire->etudiant,
            'jury' => $soutenance->jury,
            'note_finale' => $soutenance->note_finale,
            'mention' => $soutenance->mention,
            'commentaires' => $request->commentaires,
            'date_generation' => now()->format('d/m/Y H:i'),
            'genere_par' => Auth::user()->name,
        ];

        if (! Storage::disk('public')->exists('pv_soutenances')) {
            Storage::disk('public')->makeDirectory('pv_soutenances');
        }

        $filename = 'pv_soutenance_'.$soutenance->id.'_'.now()->format('Ymd_His').'.pdf';
        $path = 'pv_soutenances/'.$filename;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.proces-verbal', $data);
        Storage::disk('public')->put($path, $pdf->output());

        $procesVerbal = ProcesVerbal::create([
            'soutenance_id' => $soutenance->id,
            'fichier' => $path,
            'date_generation' => now(),
            'genere_par_id' => Auth::id(),
            'commentaires' => $request->commentaires,
            'est_signe' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Procès-verbal généré avec succès.',
            'data' => $procesVerbal->load('generePar'),
        ], 201);
    }

    /**
     * Afficher un procès-verbal spécifique.
     */
    public function show(Request $request, Soutenance $soutenance, ProcesVerbal $procesVerbal)
    {
        $this->authorize('view', $procesVerbal);

        $procesVerbal->load(['generePar', 'soutenance.memoire.etudiant']);

        return response()->json(['success' => true, 'data' => $procesVerbal]);
    }

    /**
     * Télécharger un procès-verbal.
     */
    public function download(Request $request, Soutenance $soutenance, ProcesVerbal $procesVerbal)
    {
        $this->authorize('view', $procesVerbal);

        if (! Storage::disk('public')->exists($procesVerbal->fichier)) {
            return response()->json(['success' => false, 'message' => 'Fichier non trouvé.'], 404);
        }

        return Storage::disk('public')->download(
            $procesVerbal->fichier,
            'PV_Soutenance_'.$soutenance->id.'.pdf'
        );
    }

    /**
     * Supprimer un procès-verbal.
     */
    public function destroy(Request $request, Soutenance $soutenance, ProcesVerbal $procesVerbal)
    {
        $this->authorize('delete', $procesVerbal);

        if (Storage::disk('public')->exists($procesVerbal->fichier)) {
            Storage::disk('public')->delete($procesVerbal->fichier);
        }

        $procesVerbal->delete();

        return response()->json(['success' => true, 'message' => 'Procès-verbal supprimé avec succès.']);
    }

    /**
     * Récupérer tous les PV d'un étudiant (utile pour la bibliothèque numérique).
     */
    public function getByEtudiant(Request $request, int $etudiantId)
    {
        $user = $request->user();
        if ($user->id !== $etudiantId && ! $user->hasRole('administration')) {
            abort(403);
        }

        $pvs = ProcesVerbal::whereHas('soutenance.memoire', function ($query) use ($etudiantId) {
            $query->where('etudiant_id', $etudiantId);
        })
            ->with(['soutenance', 'generePar'])
            ->orderBy('date_generation', 'desc')
            ->get();

        return response()->json(['success' => true, 'data' => $pvs]);
    }

    /**
     * Signer un procès-verbal.
     */
    public function signer(Request $request, Soutenance $soutenance, ProcesVerbal $procesVerbal)
    {
        $this->authorize('signer', $procesVerbal);

        if ($procesVerbal->est_signe) {
            return response()->json(['success' => false, 'message' => 'Ce PV est déjà signé.'], 422);
        }

        $user = $request->user();
        $role = match (true) {
            $user->hasRole('administration') => 'Administration',
            default => optional($soutenance->jury()->where('user_id', $user->id)->first())->role_jury ?? 'Membre',
        };

        $signatures = $procesVerbal->signatures ?? [];
        $signatures[] = [
            'signataire_id' => $user->id,
            'nom' => $user->name,
            'role' => is_string($role) ? ucfirst($role) : $role,
            'date_signature' => now()->toDateTimeString(),
        ];

        $procesVerbal->update([
            'signatures' => $signatures,
            'est_signe' => true,
            'date_signature' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'PV signé avec succès.',
            'data' => $procesVerbal,
        ]);
    }
}
