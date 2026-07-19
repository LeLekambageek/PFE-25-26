<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Memoire;
use App\Models\MemoireCorrection;
use App\Models\MemoireVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MemoireVersionController extends Controller
{
    public function index(Request $request, Memoire $memoire)
    {
        $this->authorize('view', $memoire);

        return response()->json($memoire->versions()->with('corrections.auteur')->get());
    }

    public function store(Request $request, Memoire $memoire)
    {
        $this->authorize('create', MemoireVersion::class);

        if ($memoire->etudiant_id !== $request->user()->id) {
            abort(403, "Vous ne pouvez déposer une version que sur votre propre mémoire.");
        }

        $data = $request->validate([
            'numero_version' => 'required|in:v1,v2,v3,finale',
            'fichier' => 'required|file|mimes:pdf,doc,docx|max:20480',
        ]);

        $path = $request->file('fichier')->store("memoires/{$memoire->id}", 'local');

        $version = MemoireVersion::create([
            'memoire_id' => $memoire->id,
            'soumis_par_id' => $request->user()->id,
            'numero_version' => $data['numero_version'],
            'fichier_path' => $path,
            'fichier_nom_original' => $request->file('fichier')->getClientOriginalName(),
            'statut' => 'en_attente',
        ]);

        if ($memoire->statut === 'corrections_demandees') {
            $memoire->update(['statut' => 'en_cours']);
        }

        return response()->json($version, 201);
    }

    public function corriger(Request $request, MemoireVersion $version)
    {
        $this->authorize('corriger', $version);

        $data = $request->validate([
            'commentaire' => 'required|string',
            'type_correction' => 'nullable|string|max:100',
        ]);

        $correction = MemoireCorrection::create([
            'memoire_version_id' => $version->id,
            'auteur_id' => $request->user()->id,
            'commentaire' => $data['commentaire'],
            'type_correction' => $data['type_correction'] ?? 'annotation',
        ]);

        $version->update(['statut' => 'corrige']);
        $version->memoire->update(['statut' => 'corrections_demandees']);

        return response()->json($correction, 201);
    }

    public function validerFinale(Request $request, MemoireVersion $version)
    {
        $this->authorize('validerFinale', $version);

        $version->update(['statut' => 'valide']);
        $version->memoire->update(['statut' => 'valide_final']);

        return response()->json($version);
    }

    public function download(Request $request, MemoireVersion $version)
    {
        $this->authorize('view', $version);

        return Storage::disk('local')->download($version->fichier_path, $version->fichier_nom_original);
    }
}
