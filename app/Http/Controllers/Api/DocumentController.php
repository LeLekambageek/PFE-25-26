<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    /**
     * Consultation, recherche et filtrage de la bibliotheque numerique.
     * Accessible a tous les roles disposant de la permission bibliotheque.consulter.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Document::class);

        $query = Document::with('archivePar');

        if ($request->filled('recherche')) {
            $terme = $request->string('recherche');
            $query->where(function ($q) use ($terme) {
                $q->where('titre', 'like', "%{$terme}%")
                    ->orWhere('auteur', 'like', "%{$terme}%")
                    ->orWhere('mots_cles', 'like', "%{$terme}%");
            });
        }

        if ($request->filled('type_document')) {
            $query->where('type_document', $request->string('type_document'));
        }

        if ($request->filled('annee')) {
            $query->where('annee', $request->integer('annee'));
        }

        if ($request->filled('mention')) {
            $query->where('mention', $request->string('mention'));
        }

        return response()->json($query->latest()->paginate(15));
    }

    public function show(Request $request, Document $document)
    {
        $this->authorize('view', $document);

        return response()->json($document->load(['archivePar', 'memoire', 'stage']));
    }

    /**
     * Archivage manuel d'un document dans la bibliotheque (ex: rapport ou document divers).
     * Les memoires y sont deposes automatiquement a la publication des resultats de soutenance.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Document::class);

        $data = $request->validate([
            'type_document' => 'required|in:memoire,rapport_stage,autre',
            'titre' => 'required|string|max:255',
            'auteur' => 'nullable|string|max:255',
            'annee' => 'nullable|integer|digits:4',
            'mention' => 'nullable|string|max:255',
            'mots_cles' => 'nullable|string|max:255',
            'fichier' => 'required|file|mimes:pdf,doc,docx|max:20480',
        ]);

        $filePath = $request->file('fichier')->store('bibliotheque', 'public');

        $document = Document::create([
            'type_document' => $data['type_document'],
            'titre' => $data['titre'],
            'auteur' => $data['auteur'] ?? null,
            'annee' => $data['annee'] ?? null,
            'mention' => $data['mention'] ?? null,
            'mots_cles' => $data['mots_cles'] ?? null,
            'fichier_path' => $filePath,
            'archive_par_id' => $request->user()->id,
        ]);

        return response()->json($document->load('archivePar'), 201);
    }

    /**
     * Modification des metadonnees d'un document (pas du fichier).
     */
    public function update(Request $request, Document $document)
    {
        $this->authorize('update', $document);

        $data = $request->validate([
            'titre' => 'sometimes|required|string|max:255',
            'auteur' => 'nullable|string|max:255',
            'annee' => 'nullable|integer|digits:4',
            'mention' => 'nullable|string|max:255',
            'mots_cles' => 'nullable|string|max:255',
        ]);

        $document->update($data);

        return response()->json($document->fresh()->load('archivePar'));
    }

    public function destroy(Request $request, Document $document)
    {
        $this->authorize('delete', $document);

        if ($document->fichier_path && ! $document->memoire_id && ! $document->stage_id) {
            Storage::disk('public')->delete($document->fichier_path);
        }

        $document->delete();

        return response()->json(null, 204);
    }

    public function download(Request $request, Document $document)
    {
        $this->authorize('view', $document);

        $disk = $document->type_document === 'memoire' ? 'local' : 'public';

        if (! $document->fichier_path || ! Storage::disk($disk)->exists($document->fichier_path)) {
            return response()->json(['message' => 'Fichier non trouvé'], 404);
        }

        return Storage::disk($disk)->download($document->fichier_path, $document->titre);
    }
}
