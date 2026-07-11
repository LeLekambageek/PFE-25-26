<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Encadrement;
use App\Services\EncadrementService;
use Illuminate\Http\Request;

class EncadrementController extends Controller
{
    public function __construct(private EncadrementService $encadrementService) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Encadrement::class);

        $user = $request->user();
        $query = Encadrement::query()->with(['etudiant.user', 'enseignant.user']);

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
        ]);

        $encadrement = $this->encadrementService->creer(
            $data['etudiant_id'],
            $data['enseignant_id'],
            $data['type'] ?? 'stage'
        );

        return response()->json($encadrement, 201);
    }

    public function ajouterEntree(Request $request, Encadrement $encadrement)
    {
        $this->authorize('ajouterEntree', $encadrement);

        $data = $request->validate(['contenu' => 'required|string']);
        $entry = $this->encadrementService->ajouterEntree($encadrement, $request->user()->id, $data['contenu']);

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

        return response()->json($rdv, 201);
    }

    public function cloturer(Request $request, Encadrement $encadrement)
    {
        $this->authorize('cloturer', $encadrement);
        $encadrement = $this->encadrementService->cloturer($encadrement);

        return response()->json($encadrement);
    }
}