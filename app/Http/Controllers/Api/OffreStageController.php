<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OffreStage;
use Illuminate\Http\Request;

class OffreStageController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', OffreStage::class);

        $query = OffreStage::with('entreprise')->latest();

        // Un etudiant ne voit que les offres encore ouvertes ; l'administration voit tout.
        if (! $request->user()->hasRole('administration')) {
            $query->where('statut', 'ouverte');
        }

        return response()->json($query->paginate(20));
    }

    public function show(Request $request, OffreStage $offre)
    {
        $this->authorize('view', $offre);

        return response()->json($offre->load('entreprise'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', OffreStage::class);

        $data = $request->validate([
            'entreprise_id' => 'required|exists:entreprises,id',
            'titre' => 'required|string|max:255',
            'description' => 'required|string',
            'competences_requises' => 'nullable|string',
            'date_debut_souhaitee' => 'nullable|date',
            'date_fin_souhaitee' => 'nullable|date|after:date_debut_souhaitee',
        ]);

        $offre = OffreStage::create([
            ...$data,
            'statut' => 'ouverte',
            'publiee_par_id' => $request->user()->id,
        ]);

        return response()->json($offre->load('entreprise'), 201);
    }

    public function update(Request $request, OffreStage $offre)
    {
        $this->authorize('update', $offre);

        $data = $request->validate([
            'titre' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'competences_requises' => 'nullable|string',
            'date_debut_souhaitee' => 'nullable|date',
            'date_fin_souhaitee' => 'nullable|date',
            'statut' => 'sometimes|in:ouverte,fermee',
        ]);

        $offre->update($data);

        return response()->json($offre);
    }

    public function destroy(Request $request, OffreStage $offre)
    {
        $this->authorize('delete', $offre);
        $offre->delete();

        return response()->json(null, 204);
    }
}