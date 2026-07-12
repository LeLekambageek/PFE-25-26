<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Tableau de bord - Mémoires & Soutenances</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; }
        h1 { font-size: 18px; border-bottom: 2px solid #333; padding-bottom: 8px; }
        h2 { font-size: 14px; margin-top: 24px; background: #f2f2f2; padding: 6px 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background: #eee; }
        .meta { color: #666; margin-bottom: 20px; }
        .kpi { display: inline-block; width: 30%; margin: 6px 1%; padding: 10px; border: 1px solid #ddd; }
        .kpi .valeur { font-size: 20px; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Tableau de bord - Mémoires & Soutenances</h1>
    <p class="meta">EPF Africa - Généré le {{ $genere_le }}</p>

    <h2>Indicateurs Mémoires</h2>
    <div>
        @foreach ($memoires as $cle => $valeur)
            <div class="kpi">
                <div>{{ str_replace('_', ' ', ucfirst($cle)) }}</div>
                <div class="valeur">{{ $valeur }}</div>
            </div>
        @endforeach
    </div>

    <h2>Indicateurs Soutenances</h2>
    <div>
        @foreach ($soutenances as $cle => $valeur)
            <div class="kpi">
                <div>{{ str_replace('_', ' ', ucfirst($cle)) }}</div>
                <div class="valeur">{{ $valeur }}</div>
            </div>
        @endforeach
    </div>

    <h2>Répartition des mémoires par statut</h2>
    <table>
        <thead><tr><th>Statut</th><th>Nombre</th></tr></thead>
        <tbody>
            @foreach ($parStatut as $statut => $total)
                <tr><td>{{ $statut }}</td><td>{{ $total }}</td></tr>
            @endforeach
        </tbody>
    </table>

    <h2>Planning des prochaines soutenances</h2>
    <table>
        <thead>
            <tr><th>Étudiant</th><th>Titre</th><th>Date</th><th>Heure</th><th>Salle</th><th>Jury complet</th></tr>
        </thead>
        <tbody>
            @forelse ($planning as $s)
                <tr>
                    <td>{{ $s['etudiant'] }}</td>
                    <td>{{ $s['titre_memoire'] }}</td>
                    <td>{{ $s['date'] }}</td>
                    <td>{{ $s['heure'] }}</td>
                    <td>{{ $s['salle'] }}</td>
                    <td>{{ $s['jury_complet'] ? 'Oui' : 'Non' }}</td>
                </tr>
            @empty
                <tr><td colspan="6">Aucune soutenance planifiée.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
