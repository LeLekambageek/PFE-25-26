<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Entreprise;
use Illuminate\Http\Request;

class EntrepriseController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Entreprise::class);

        $query = Entreprise::withCount([
            'stages',
            'stages as stages_actifs_count' => fn ($q) => $q->whereIn('statut', ['valide', 'en_cours']),
            'stages as stages_termines_count' => fn ($q) => $q->where('statut', 'termine'),
        ])->orderBy('raison_sociale');

        if ($request->has('secteur')) {
            $query->where('secteur_activite', 'like', '%'.$request->secteur.'%');
        }

        return response()->json($query->get());
    }

    public function show(Request $request, Entreprise $entreprise)
    {
        $this->authorize('view', $entreprise);

        return response()->json($entreprise->loadCount([
            'stages',
            'stages as stages_actifs_count' => fn ($q) => $q->whereIn('statut', ['valide', 'en_cours']),
            'stages as stages_termines_count' => fn ($q) => $q->where('statut', 'termine'),
        ]));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Entreprise::class);

        $data = $request->validate([
            'raison_sociale' => 'required|string|max:255',
            'secteur_activite' => 'nullable|string|max:255',
            'contact_nom' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_telephone' => 'nullable|string|max:30',
        ]);

        $entreprise = Entreprise::create($data);

        return response()->json($entreprise, 201);
    }

    public function update(Request $request, Entreprise $entreprise)
    {
        $this->authorize('update', $entreprise);

        $data = $request->validate([
            'raison_sociale' => 'sometimes|required|string|max:255',
            'secteur_activite' => 'nullable|string|max:255',
            'contact_nom' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_telephone' => 'nullable|string|max:30',
        ]);

        $entreprise->update($data);

        return response()->json($entreprise);
    }

    public function destroy(Request $request, Entreprise $entreprise)
    {
        $this->authorize('delete', $entreprise);
        $entreprise->delete();

        return response()->json(null, 204);
    }
}