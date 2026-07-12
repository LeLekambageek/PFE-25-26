<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Memoire;
use App\Models\Soutenance;
use App\Models\User;
use Illuminate\Http\Request;

class SoutenanceController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Soutenance::class);
        $user = $request->user();

        $query = Soutenance::with(['memoire.etudiant', 'jury.membre']);

        if ($user->hasRole('etudiant')) {
            $query->whereHas('memoire', fn ($q) => $q->where('etudiant_id', $user->id));
        } elseif ($user->hasRole('jury_soutenance') && ! $user->hasAnyRole(['admin_general', 'responsable_formation'])) {
            $query->whereHas('jury', fn ($q) => $q->where('user_id', $user->id));
        } elseif ($user->hasRole('enseignant_encadreur') && ! $user->hasAnyRole(['admin_general', 'responsable_formation'])) {
            $query->whereHas('memoire', fn ($q) => $q->where('encadreur_id', $user->id));
        }

        return response()->json($query->orderBy('date_soutenance')->paginate(15));
    }

    public function show(Request $request, Soutenance $soutenance)
    {
        $this->authorize('view', $soutenance);

        return response()->json($soutenance->load(['memoire.etudiant', 'memoire.encadreur', 'jury.membre', 'notes.jury']));
    }

    /**
     * Planification d'une soutenance (calendrier, salle, horaire).
     */
    public function store(Request $request)
    {
        $this->authorize('create', Soutenance::class);

        $data = $request->validate([
            'memoire_id' => 'required|exists:memoires,id',
            'date_soutenance' => 'required|date',
            'heure_debut' => 'required',
            'salle' => 'required|string|max:100',
        ]);

        $memoire = Memoire::findOrFail($data['memoire_id']);
        if ($memoire->statut !== 'valide_final') {
            return response()->json([
                'message' => "Le mémoire doit être validé (version finale) avant de planifier une soutenance.",
            ], 422);
        }

        $soutenance = Soutenance::create([
            ...$data,
            'planifiee_par_id' => $request->user()->id,
            'statut' => 'planifiee',
        ]);

        return response()->json($soutenance, 201);
    }

    public function update(Request $request, Soutenance $soutenance)
    {
        $this->authorize('update', $soutenance);

        $data = $request->validate([
            'date_soutenance' => 'sometimes|date',
            'heure_debut' => 'sometimes',
            'salle' => 'sometimes|string|max:100',
            'statut' => 'sometimes|in:planifiee,en_cours,terminee,annulee',
        ]);

        $soutenance->update($data);

        return response()->json($soutenance);
    }

    public function destroy(Request $request, Soutenance $soutenance)
    {
        $this->authorize('delete', $soutenance);
        $soutenance->delete();

        return response()->json(null, 204);
    }

    /**
     * Composition automatique/manuelle du jury (président, rapporteur, examinateur).
     */
    public function composerJury(Request $request, Soutenance $soutenance)
    {
        $this->authorize('gererJury', Soutenance::class);

        $data = $request->validate([
            'membres' => 'required|array|min:1',
            'membres.*.user_id' => 'required|exists:users,id',
            'membres.*.role_jury' => 'required|in:president,rapporteur,examinateur',
        ]);

        foreach ($data['membres'] as $membre) {
            $enseignant = User::findOrFail($membre['user_id']);
            if (! $enseignant->hasAnyRole(['jury_soutenance', 'enseignant_encadreur'])) {
                return response()->json([
                    'message' => "L'utilisateur {$enseignant->name} n'a pas de rôle jury/enseignant.",
                ], 422);
            }

            $soutenance->jury()->updateOrCreate(
                ['user_id' => $membre['user_id']],
                ['role_jury' => $membre['role_jury']]
            );
        }

        return response()->json($soutenance->load('jury.membre'));
    }

    /**
     * Génération et envoi des convocations au jury.
     */
    public function envoyerConvocations(Request $request, Soutenance $soutenance)
    {
        $this->authorize('gererJury', Soutenance::class);

        $soutenance->jury()->update([
            'convocation_envoyee' => true,
            'convoque_a' => now(),
        ]);

        // L'envoi effectif (mail/notification) sera branché sur une Notification Laravel dédiée.
        return response()->json([
            'message' => 'Convocations envoyées aux membres du jury.',
            'jury' => $soutenance->jury()->with('membre')->get(),
        ]);
    }

    /**
     * Publication des résultats une fois toutes les notes saisies.
     */
    public function publierResultats(Request $request, Soutenance $soutenance)
    {
        $this->authorize('publierResultats', Soutenance::class);

        if ($soutenance->notes()->count() === 0) {
            return response()->json(['message' => "Aucune note saisie pour cette soutenance."], 422);
        }

        $moyenne = $soutenance->notes()->avg('note');
        $mention = match (true) {
            $moyenne >= 16 => 'Très Bien',
            $moyenne >= 14 => 'Bien',
            $moyenne >= 12 => 'Assez Bien',
            $moyenne >= 10 => 'Passable',
            default => 'Ajourné',
        };

        $soutenance->update([
            'note_finale' => round($moyenne, 2),
            'mention' => $mention,
            'statut' => 'terminee',
            'resultats_publies' => true,
        ]);

        $soutenance->memoire->update(['statut' => 'soutenu']);

        return response()->json($soutenance);
    }
}
