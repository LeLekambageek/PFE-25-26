<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Procès-verbal de Soutenance</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1F2937; line-height: 1.5; margin: 20px; }
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .logo-cell { width: 80px; text-align: left; }
        .logo-placeholder { width: 70px; height: 70px; background: #3D2B7A; color: #fff; line-height: 70px; text-align: center; border-radius: 8px; font-weight: bold; font-size: 16px; }
        .title-cell { text-align: center; font-size: 16px; font-weight: bold; color: #3D2B7A; text-transform: uppercase; }
        .subtitle { font-size: 10px; color: #4B5563; font-weight: normal; margin-top: 4px; }
        
        .section-title { font-size: 12px; font-weight: bold; color: #3D2B7A; border-bottom: 2px solid #E5E7EB; padding-bottom: 4px; margin-top: 25px; margin-bottom: 12px; }
        
        .info-grid { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .info-grid td { padding: 6px 0; vertical-align: top; }
        .info-label { font-weight: bold; width: 180px; color: #4B5563; }
        
        .table-custom { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 20px; }
        .table-custom th, .table-custom td { border: 1px solid #E5E7EB; padding: 8px 10px; text-align: left; }
        .table-custom th { background-color: #F3F4F6; color: #3D2B7A; font-weight: bold; }
        
        .result-box { background: #FEF3C7; border: 2px dashed #F59E0B; border-radius: 8px; padding: 15px; margin-top: 20px; text-align: center; }
        .result-box .score { font-size: 24px; font-weight: bold; color: #3D2B7A; margin-bottom: 4px; }
        .result-box .mention { font-size: 14px; font-weight: bold; color: #F59E0B; text-transform: uppercase; }
        
        .signature-section { width: 100%; margin-top: 40px; border-collapse: collapse; }
        .signature-cell { width: 50%; text-align: center; vertical-align: top; }
        .signature-title { font-weight: bold; color: #3D2B7A; margin-bottom: 50px; }
        .signature-line { border-bottom: 1px solid #9CA3AF; width: 150px; margin: 0 auto 8px auto; }
        .signed-badge { color: #10B981; font-weight: bold; font-size: 10px; }
        
        .footer { position: fixed; bottom: 0; left: 0; right: 0; text-align: center; font-size: 9px; color: #9CA3AF; border-top: 1px solid #E5E7EB; padding-top: 8px; }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td class="logo-cell">
                <div class="logo-placeholder">EPF</div>
            </td>
            <td class="title-cell">
                PROCÈS-VERBAL DE SOUTENANCE
                <div class="subtitle">Système de Gestion Académique des Mémoires — EPF Africa</div>
            </td>
        </tr>
    </table>

    <div class="section-title">Informations sur l'étudiant et le mémoire</div>
    <table class="info-grid">
        <tr>
            <td class="info-label">Étudiant :</td>
            <td>{{ $etudiant->name }} ({{ $etudiant->email }})</td>
        </tr>
        <tr>
            <td class="info-label">Filière / Niveau :</td>
            <td>{{ $etudiant->etudiant->filiere ?? '-' }} / {{ $etudiant->etudiant->niveau ?? '-' }}</td>
        </tr>
        <tr>
            <td class="info-label">Titre du mémoire :</td>
            <td style="font-weight: bold; color: #3D2B7A;">{{ $soutenance->memoire->titre }}</td>
        </tr>
        <tr>
            <td class="info-label">Encadreur principal :</td>
            <td>{{ $soutenance->memoire->encadreur->name ?? 'Non assigné' }}</td>
        </tr>
    </table>

    <div class="section-title">Détails de la soutenance</div>
    <table class="info-grid">
        <tr>
            <td class="info-label">Date et Heure :</td>
            <td>Le {{ $soutenance->date_soutenance?->format('d/m/Y') }} à {{ $soutenance->heure_debut }}</td>
        </tr>
        <tr>
            <td class="info-label">Salle :</td>
            <td>{{ $soutenance->salle }}</td>
        </tr>
        <tr>
            <td class="info-label">Généré le :</td>
            <td>{{ $date_generation }} (par {{ $genere_par }})</td>
        </tr>
    </table>

    <div class="section-title">Composition du Jury</div>
    <table class="table-custom">
        <thead>
            <tr>
                <th>Membre</th>
                <th>Rôle dans le Jury</th>
                <th>Statut de Validation</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($jury as $j)
                <tr>
                    <td>{{ $j->membre->name ?? '-' }}</td>
                    <td style="text-transform: capitalize;">{{ $j->role_jury }}</td>
                    <td>
                        @if ($j->notes_validees)
                            <span style="color: #10B981; font-weight: bold;">Notes Validées</span>
                        @else
                            <span style="color: #F59E0B;">En attente de validation</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="section-title">Résultats & Délibération</div>
    <div class="result-box">
        @php
            $noteFinalPrint = $note_finale ?? round($soutenance->notes()->avg('note') ?? 0, 2);
            $mentionPrint = $mention ?? match (true) {
                $noteFinalPrint >= 18 => 'Excellent',
                $noteFinalPrint >= 16 => 'Très Bien',
                $noteFinalPrint >= 14 => 'Bien',
                $noteFinalPrint >= 12 => 'Assez Bien',
                $noteFinalPrint >= 10 => 'Passable',
                default => 'Ajourné',
            };
        @endphp
        <div class="score">Note Globale : {{ number_format($noteFinalPrint, 2) }} / 20</div>
        <div class="mention">Mention : {{ $mentionPrint }}</div>
    </div>

    @if($commentaires)
        <div class="section-title">Observations / Commentaires additionnels</div>
        <p style="background: #F9FAFB; padding: 10px; border-left: 3px solid #3D2B7A; margin: 0; font-style: italic;">
            "{{ $commentaires }}"
        </p>
    @endif

    <table class="signature-section">
        <tr>
            <td class="signature-cell">
                <div class="signature-title">Le Président du Jury</div>
                <div class="signature-line"></div>
                @php
                    $presidentJury = $jury->where('role_jury', 'president')->first();
                    $isSignedByPresident = $presidentJury?->notes_validees ?? false;
                @endphp
                @if($isSignedByPresident)
                    <div class="signed-badge">✓ Validé électroniquement</div>
                    <div style="font-size: 8px; color: #6B7280;">le {{ $presidentJury->notes_validees_a?->format('d/m/Y H:i') }}</div>
                @else
                    <div style="font-size: 9px; color: #9CA3AF;">Signature en attente</div>
                @endif
            </td>
            <td class="signature-cell">
                <div class="signature-title">Le Représentant de l'Administration</div>
                <div class="signature-line"></div>
                <div class="signed-badge">✓ Document Certifié Conforme</div>
                <div style="font-size: 8px; color: #6B7280;">généré par l'administration</div>
            </td>
        </tr>
    </table>

    <div class="footer">
        EPF Africa — Système de Gestion Académique des Mémoires et Soutenances. Ce procès-verbal est un document officiel certifié.
    </div>
</body>
</html>
