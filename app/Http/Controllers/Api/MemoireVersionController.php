<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Memoire;
use App\Models\MemoireCorrection;
use App\Models\MemoireVersion;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MemoireVersionController extends Controller
{
    /**
     * Liste des versions déposées pour un mémoire (V1, V2, V3, finale).
     */
    public function index(Request $request, Memoire $memoire)
    {
        $this->authorize('view', $memoire);

        return response()->json($memoire->versions()->with('corrections.auteur')->get());
    }

    /**
     * Dépôt d'une nouvelle version par l'étudiant.
     */
    public function store(Request $request, Memoire $memoire)
    {
        $this->authorize('create', MemoireVersion::class);

        if ($memoire->etudiant_id !== $request->user()->id) {
            abort(403, "Vous ne pouvez déposer une version que sur votre propre mémoire.");
        }

        if ($memoire->estVerrouillePourSoutenance()) {
            return response()->json([
                'message' => 'Le dépôt de nouvelles versions est verrouillé (moins de 5 jours avant la soutenance).',
            ], 422);
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

        if ($memoire->encadreur) {
            app(NotificationService::class)->nouveauMemoireDepose($memoire);
        }

        return response()->json($version, 201);
    }

    /**
     * Annotations, commentaires, recommandations et suivi d'avancement par
     * l'encadreur. Le champ "commentaire" (facultatif) ouvre en plus une demande
     * de correction formelle qui repasse le mémoire en "corrections_demandees".
     */
    public function corriger(Request $request, MemoireVersion $version)
    {
        $this->authorize('corriger', $version);

        $data = $request->validate([
            'commentaire' => 'nullable|string',
            'type_correction' => 'nullable|string|max:100',
            'annotations' => 'nullable|string',
            'commentaires_encadreur' => 'nullable|string',
            'recommandations' => 'nullable|string',
            'pourcentage_avancement' => 'nullable|integer|min:0|max:100',
        ]);

        if (! empty($data['commentaire'])) {
            MemoireCorrection::create([
                'memoire_version_id' => $version->id,
                'auteur_id' => $request->user()->id,
                'commentaire' => $data['commentaire'],
                'type_correction' => $data['type_correction'] ?? 'annotation',
            ]);

            $version->update(['statut' => 'corrige']);
            $version->memoire->update(['statut' => 'corrections_demandees']);
        }

        $version->fill(array_filter([
            'annotations' => $data['annotations'] ?? null,
            'commentaires_encadreur' => $data['commentaires_encadreur'] ?? null,
            'recommandations' => $data['recommandations'] ?? null,
        ], fn ($value) => $value !== null))->save();

        if (isset($data['pourcentage_avancement'])) {
            $version->mettreAJourAvancement($data['pourcentage_avancement']);
        }

        app(NotificationService::class)->nouveauDocumentAnnote($version->memoire);

        return response()->json($version->fresh()->load('corrections'));
    }

    /**
     * Validation finale de la version par l'encadreur / chef de département.
     * Exige un avancement d'au moins 80% et rend l'étudiant éligible à la
     * planification de sa soutenance.
     */
    public function validerFinale(Request $request, MemoireVersion $version)
    {
        $this->authorize('validerFinale', $version);

        if ((int) $version->pourcentage_avancement < 80) {
            return response()->json([
                'message' => 'Le mémoire doit être à au moins 80% pour être validé comme version finale.',
            ], 422);
        }

        $version->update(['statut' => 'valide']);
        $version->memoire->update(['statut' => 'valide_final']);

        $notificationService = app(NotificationService::class);
        $notificationService->memoireValideFinal($version->memoire);
        $notificationService->validationFinaleMemoire($version->memoire);

        return response()->json($version);
    }

    /**
     * Téléchargement du fichier d'une version.
     */
    public function download(Request $request, MemoireVersion $version)
    {
        $this->authorize('view', $version);

        return Storage::disk('local')->download($version->fichier_path, $version->fichier_nom_original);
    }
}
