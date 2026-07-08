<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Soutenance;
use Illuminate\Http\Request;

class SoutenanceNoteController extends Controller
{
    /**
     * Grille de notation : chaque membre du jury note plusieurs critères.
     */
    public function store(Request $request, Soutenance $soutenance)
    {
        $this->authorize('noter', $soutenance);

        $data = $request->validate([
            'notes' => 'required|array|min:1',
            'notes.*.critere' => 'required|string|max:100',
            'notes.*.note' => 'required|numeric|min:0|max:20',
            'notes.*.commentaire' => 'nullable|string',
        ]);

        $resultats = [];
        foreach ($data['notes'] as $noteData) {
            $resultats[] = $soutenance->notes()->updateOrCreate(
                ['jury_id' => $request->user()->id, 'critere' => $noteData['critere']],
                ['note' => $noteData['note'], 'commentaire' => $noteData['commentaire'] ?? null]
            );
        }

        return response()->json($resultats, 201);
    }

    public function index(Request $request, Soutenance $soutenance)
    {
        $this->authorize('view', $soutenance);

        return response()->json($soutenance->notes()->with('jury')->get());
    }
}
