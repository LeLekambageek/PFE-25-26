<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Exports\MemoiresParStatutExport;
use App\Models\Memoire;
use App\Models\Soutenance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class DashboardController extends Controller
{
    /**
     * Vue d'ensemble : indicateurs clés Mémoires + Soutenances.
     * Accessible aux profils décisionnels (admin, responsable de formation).
     */
    public function apercu(Request $request)
    {
        $this->autoriserAccesDashboard($request);

        return response()->json([
            'memoires' => $this->indicateursMemoires(),
            'soutenances' => $this->indicateursSoutenances(),
        ]);
    }

    /**
     * Nombre de mémoires par statut (proposé, validé, en cours, corrections, validé final, soutenu, rejeté).
     */
    public function memoiresParStatut(Request $request)
    {
        $this->autoriserAccesDashboard($request);

        $repartition = Memoire::select('statut', DB::raw('count(*) as total'))
            ->groupBy('statut')
            ->pluck('total', 'statut');

        return response()->json([
            'total' => Memoire::count(),
            'par_statut' => $repartition,
        ]);
    }

    /**
     * Taux d'encadrement : nombre de mémoires encadrés par enseignant.
     */
    public function tauxEncadrement(Request $request)
    {
        $this->autoriserAccesDashboard($request);

        $repartition = Memoire::query()
            ->whereNotNull('encadreur_id')
            ->join('users', 'users.id', '=', 'memoires.encadreur_id')
            ->select('users.id as encadreur_id', 'users.name as encadreur', DB::raw('count(memoires.id) as nombre_memoires'))
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('nombre_memoires')
            ->get();

        return response()->json($repartition);
    }

    /**
     * Planning des soutenances à venir (statut planifiee, triées par date).
     */
    public function planningSoutenances(Request $request)
    {
        $this->autoriserAccesDashboard($request);

        $soutenances = Soutenance::with(['memoire.etudiant', 'jury.membre'])
            ->where('statut', 'planifiee')
            ->orderBy('date_soutenance')
            ->orderBy('heure_debut')
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'etudiant' => $s->memoire->etudiant->name ?? null,
                'titre_memoire' => $s->memoire->titre,
                'date' => $s->date_soutenance,
                'heure' => $s->heure_debut,
                'salle' => $s->salle,
                'jury_complet' => $s->jury->count() >= 3,
                'membres_jury' => $s->jury->pluck('membre.name'),
            ]);

        return response()->json($soutenances);
    }

    /**
     * Répartition des résultats/mentions des soutenances déjà publiées.
     */
    public function resultatsSoutenances(Request $request)
    {
        $this->autoriserAccesDashboard($request);

        $publiees = Soutenance::where('resultats_publies', true);

        $parMention = (clone $publiees)
            ->select('mention', DB::raw('count(*) as total'))
            ->groupBy('mention')
            ->pluck('total', 'mention');

        return response()->json([
            'total_soutenues' => $publiees->count(),
            'note_moyenne_generale' => round((clone $publiees)->avg('note_finale') ?? 0, 2),
            'par_mention' => $parMention,
        ]);
    }

    /**
     * Indicateur de délai moyen entre proposition du sujet et soutenance (en jours).
     */
    public function delaisMoyens(Request $request)
    {
        $this->autoriserAccesDashboard($request);

        $memoires = Memoire::query()
            ->join('soutenances', 'soutenances.memoire_id', '=', 'memoires.id')
            ->whereNotNull('memoires.date_proposition')
            ->select('soutenances.date_soutenance', 'memoires.date_proposition')
            ->get();

        $joursList = $memoires->map(function ($m) {
            $dateSoutenance = \Carbon\Carbon::parse($m->date_soutenance);
            $dateProposition = \Carbon\Carbon::parse($m->date_proposition);
            return $dateProposition->diffInDays($dateSoutenance);
        });

        return response()->json([
            'delai_moyen_jours' => $joursList->isNotEmpty() ? round($joursList->avg(), 1) : null,
            'nombre_cas' => $joursList->count(),
        ]);
    }

    /**
     * Export PDF du tableau de bord (nécessite composer require barryvdh/laravel-dompdf).
     */
    public function exporterPdf(Request $request)
    {
        $this->autoriserAccesDashboard($request);

        $parStatut = Memoire::select('statut', DB::raw('count(*) as total'))
            ->groupBy('statut')
            ->pluck('total', 'statut');

        $planning = Soutenance::with(['memoire.etudiant', 'jury'])
            ->where('statut', 'planifiee')
            ->orderBy('date_soutenance')
            ->get()
            ->map(fn ($s) => [
                'etudiant' => $s->memoire->etudiant->name ?? '-',
                'titre_memoire' => $s->memoire->titre,
                'date' => $s->date_soutenance,
                'heure' => $s->heure_debut,
                'salle' => $s->salle,
                'jury_complet' => $s->jury->count() >= 3,
            ]);

        $pdf = Pdf::loadView('pdf.dashboard', [
            'genere_le' => now()->format('d/m/Y H:i'),
            'memoires' => $this->indicateursMemoires(),
            'soutenances' => $this->indicateursSoutenances(),
            'parStatut' => $parStatut,
            'planning' => $planning,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('tableau-de-bord-'.now()->format('Ymd_His').'.pdf');
    }

    /**
     * Export Excel (.xlsx) des mémoires par statut (nécessite composer require maatwebsite/excel).
     */
    public function exporterExcel(Request $request)
    {
        $this->autoriserAccesDashboard($request);

        return Excel::download(new MemoiresParStatutExport, 'memoires-par-statut-'.now()->format('Ymd_His').'.xlsx');
    }

    /**
     * Export CSV natif (aucune dépendance) : fonctionne immédiatement, s'ouvre dans Excel.
     */
    public function exporterCsv(Request $request)
    {
        $this->autoriserAccesDashboard($request);

        $memoires = Memoire::with(['etudiant', 'encadreur'])->get();

        $filename = 'memoires-'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($memoires) {
            $handle = fopen('php://output', 'w');
            // BOM UTF-8 pour un affichage correct des accents dans Excel
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['ID', 'Titre', 'Étudiant', 'Encadreur', 'Statut', 'Date proposition']);

            foreach ($memoires as $m) {
                fputcsv($handle, [
                    $m->id,
                    $m->titre,
                    $m->etudiant->name ?? '-',
                    $m->encadreur->name ?? '-',
                    $m->statut,
                    optional($m->date_proposition)->format('d/m/Y'),
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Données formatées pour les librairies de graphiques (recharts, chart.js...)
     * côté React : tableaux d'objets {label, value} plutôt que des maps associatives.
     */
    public function graphiques(Request $request)
    {
        $this->autoriserAccesDashboard($request);

        $memoiresParStatut = Memoire::select('statut', DB::raw('count(*) as value'))
            ->groupBy('statut')
            ->get()
            ->map(fn ($row) => ['label' => $row->statut, 'value' => $row->value]);

        $mentionsSoutenances = Soutenance::where('resultats_publies', true)
            ->select('mention', DB::raw('count(*) as value'))
            ->groupBy('mention')
            ->get()
            ->map(fn ($row) => ['label' => $row->mention ?? 'Non renseignée', 'value' => $row->value]);

        $encadrementParEnseignant = Memoire::query()
            ->whereNotNull('encadreur_id')
            ->join('users', 'users.id', '=', 'memoires.encadreur_id')
            ->select('users.name as label', DB::raw('count(memoires.id) as value'))
            ->groupBy('users.name')
            ->orderByDesc('value')
            ->get();

        return response()->json([
            'memoires_par_statut' => $memoiresParStatut,
            'mentions_soutenances' => $mentionsSoutenances,
            'encadrement_par_enseignant' => $encadrementParEnseignant,
        ]);
    }

    private function autoriserAccesDashboard(Request $request): void
    {
        if (! $request->user()->hasRole('administration')) {
            abort(403, "Accès réservé aux profils décisionnels (administrateur / responsable de formation).");
        }
    }

    private function indicateursMemoires(): array
    {
        return [
            'total' => Memoire::count(),
            'en_cours' => Memoire::whereIn('statut', ['en_cours', 'corrections_demandees'])->count(),
            'valides_final' => Memoire::where('statut', 'valide_final')->count(),
            'soutenus' => Memoire::where('statut', 'soutenu')->count(),
            'rejetes' => Memoire::where('statut', 'rejete')->count(),
            'sans_encadreur' => Memoire::whereNull('encadreur_id')->count(),
        ];
    }

    private function indicateursSoutenances(): array
    {
        return [
            'total' => Soutenance::count(),
            'planifiees' => Soutenance::where('statut', 'planifiee')->count(),
            'terminees' => Soutenance::where('statut', 'terminee')->count(),
            'resultats_publies' => Soutenance::where('resultats_publies', true)->count(),
            'sans_jury_complet' => Soutenance::withCount('jury')
                ->get()
                ->where('jury_count', '<', 3)
                ->count(),
        ];
    }
}
