<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Encadrement;
use App\Models\Memoire;
use App\Models\Stage;
use App\Services\EncadrementService;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class EncadrementController extends Controller
{
    public function __construct(private EncadrementService $encadrementService) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Encadrement::class);

        $user = $request->user();
        $query = Encadrement::query()->with(['etudiant.user', 'enseignant.user', 'encadrable']);

        if ($user->hasRole('etudiant')) {
            $query->where('etudiant_id', $user->etudiant->id);
        } elseif ($user->hasRole('enseignant_encadreur')) {
            $query->where('enseignant_id', $user->enseignant->id);
        }

        return response()->json($query->paginate(20));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Encadrement::class);

        $data = $request->validate([
            'etudiant_id' => 'required|exists:etudiants,id',
            'enseignant_id' => 'required|exists:enseignants,id',
            'type' => 'nullable|string|in:stage,memoire',
            'memoire_id' => 'required_if:type,memoire|nullable|exists:memoires,id',
        ]);

        $type = $data['type'] ?? 'stage';

        // Ordre imposé : un stage doit d'abord être affecté à l'étudiant avant
        // qu'un encadreur ne puisse lui être affecté.
        if ($type === 'stage') {
            $stage = Stage::where('etudiant_id', $data['etudiant_id'])
                ->whereIn('statut', ['valide', 'en_cours'])
                ->latest()
                ->first();

            if (! $stage) {
                return response()->json([
                    'message' => "Aucun stage actif pour cet étudiant : affectez d'abord un stage avant l'encadreur.",
                ], 422);
            }

            $encadrable = $stage;
        } else {
            $encadrable = Memoire::findOrFail($data['memoire_id']);
        }

        $encadrement = $this->encadrementService->creer(
            $data['etudiant_id'],
            $data['enseignant_id'],
            $type,
            $encadrable
        );

        return response()->json($encadrement->load('encadrable'), 201);
    }

    /**
     * Fil de discussion entre étudiant et encadreur (espace de discussion).
     */
    public function entrees(Request $request, Encadrement $encadrement)
    {
        $this->authorize('view', $encadrement);

        return response()->json($encadrement->entries()->with('auteur')->latest()->get());
    }

    public function ajouterEntree(Request $request, Encadrement $encadrement)
    {
        $this->authorize('ajouterEntree', $encadrement);

        $data = $request->validate(['contenu' => 'required|string']);
        $entry = $this->encadrementService->ajouterEntree($encadrement, $request->user()->id, $data['contenu']);

        $user = $request->user();
        $notificationService = app(NotificationService::class);
        if ($user->hasRole('enseignant_encadreur')) {
            $notificationService->nouveauCommentaireEncadreur($encadrement, $data['contenu']);
        } elseif ($user->hasRole('etudiant')) {
            $notificationService->reponseEtudiant($encadrement);
        }

        return response()->json($entry, 201);
    }

    public function planifierRdv(Request $request, Encadrement $encadrement)
    {
        $this->authorize('planifierRdv', $encadrement);

        $data = $request->validate([
            'date_prevue' => 'required|date',
            'sujet' => 'nullable|string',
        ]);

        $rdv = $this->encadrementService->planifierRdv($encadrement, $data['date_prevue'], $data['sujet'] ?? null);

        app(NotificationService::class)->nouveauRendezVous($encadrement);

        return response()->json($rdv, 201);
    }

    public function cloturer(Request $request, Encadrement $encadrement)
    {
        $this->authorize('cloturer', $encadrement);
        $encadrement = $this->encadrementService->cloturer($encadrement);

        return response()->json($encadrement);
    }

    public function modifier(Request $request, Encadrement $encadrement)
    {
        $this->authorize('update', $encadrement);

        $data = $request->validate(['enseignant_id' => 'required|exists:enseignants,id']);
        $encadrement = $this->encadrementService->modifier($encadrement, $data['enseignant_id']);

        return response()->json($encadrement);
    }
}