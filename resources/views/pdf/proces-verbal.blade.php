<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Procès-verbal de soutenance</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 13px; color: #222; }
        .entete { text-align: center; margin-bottom: 30px; }
        .entete h1 { font-size: 18px; margin-bottom: 4px; }
        .entete p { color: #555; margin: 2px 0; }
        .section { margin-top: 22px; }
        .section h2 { font-size: 14px; background: #f2f2f2; padding: 6px 10px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background: #eee; }
        .info-ligne { margin-bottom: 4px; }
        .info-label { display: inline-block; width: 180px; font-weight: bold; }
        .mention { font-size: 16px; font-weight: bold; text-align: center; margin-top: 10px; }
        .note-finale { font-size: 22px; font-weight: bold; text-align: center; margin-top: 6px; }
        .signatures { margin-top: 50px; }
        .signature-bloc { display: inline-block; width: 30%; text-align: center; vertical-align: top; }
        .commentaires { margin-top: 20px; padding: 10px; border: 1px solid #ddd; background: #fafafa; }
        .footer { margin-top: 40px; font-size: 10px; color: #888; text-align: center; }
    </style>
</head>
<body>
    <div class="entete">
        <h1>PROCÈS-VERBAL DE SOUTENANCE</h1>
        <p>EPF Africa - Année académique {{ $anneeAcademique }}</p>
        <p>Généré le {{ $dateGeneration }} par {{ $generePar }}</p>
    </div>

    <div class="section">
        <h2>Informations sur le mémoire</h2>
        <div class="info-ligne"><span class="info-label">Titre du mémoire :</span> {{ $memoire->titre }}</div>
        <div class="info-ligne"><span class="info-label">Étudiant :</span> {{ $etudiant->name ?? '-' }}</div>
        <div class="info-ligne"><span class="info-label">Encadreur :</span> {{ $memoire->encadreur->name ?? '-' }}</div>
        <div class="info-ligne"><span class="info-label">Date de soutenance :</span> {{ $soutenance->date_soutenance }}</div>
        <div class="info-ligne"><span class="info-label">Heure :</span> {{ $soutenance->heure_debut }}</div>
        <div class="info-ligne"><span class="info-label">Salle :</span> {{ $soutenance->salle }}</div>
    </div>

    <div class="section">
        <h2>Composition du jury</h2>
        <table>
            <thead><tr><th>Nom</th><th>Rôle</th></tr></thead>
            <tbody>
                @foreach ($jury as $membre)
                    <tr>
                        <td>{{ $membre->membre->name ?? '-' }}</td>
                        <td>{{ ucfirst($membre->role_jury) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Détail des notes</h2>
        <table>
            <thead><tr><th>Membre du jury</th><th>Critère</th><th>Note /20</th></tr></thead>
            <tbody>
                @foreach ($notes as $note)
                    <tr>
                        <td>{{ $note->jury->name ?? '-' }}</td>
                        <td>{{ ucfirst($note->critere) }}</td>
                        <td>{{ $note->note }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="note-finale">Note finale : {{ $soutenance->note_finale }} / 20</div>
        <div class="mention">Mention : {{ $soutenance->mention }}</div>
    </div>

    @if ($commentaires)
        <div class="commentaires">
            <strong>Commentaires du jury :</strong>
            <p>{{ $commentaires }}</p>
        </div>
    @endif

    <div class="signatures">
        @foreach ($jury as $membre)
            <div class="signature-bloc">
                <p>_______________________</p>
                <p>{{ $membre->membre->name ?? '-' }}</p>
                <p>{{ ucfirst($membre->role_jury) }}</p>
            </div>
        @endforeach
    </div>

    <div class="footer">
        Document généré automatiquement par la plateforme de gestion académique EPF Africa.
    </div>
</body>
</html>