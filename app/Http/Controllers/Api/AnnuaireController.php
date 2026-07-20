<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Encadrement;
use App\Models\Enseignant;
use App\Models\Etudiant;
use Illuminate\Http\Request;

class AnnuaireController extends Controller
{
    public function etudiants(Request $request)
    {
        $this->authorize('create', Encadrement::class);

        return response()->json(
            Etudiant::with('user')->get()->map(fn ($e) => [
                'id' => $e->user_id,
                'etudiant_id' => $e->id,
                'nom' => $e->user->name,
                'matricule' => $e->matricule,
            ])
        );
    }

    public function enseignants(Request $request)
    {
        $this->authorize('create', Encadrement::class);

        return response()->json(
            Enseignant::with('user')->get()->map(fn ($e) => [
                'id' => $e->user_id,
                'enseignant_id' => $e->id,
                'nom' => $e->user->name,
                'specialite' => $e->specialite,
            ])
        );
    }
}