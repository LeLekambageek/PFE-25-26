<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RapportStage extends Model
{
    use HasFactory;

    protected $table = 'rapports_stage';

    protected $fillable = [
        'stage_id', 'etudiant_id', 'type_rapport', 'fichier_path',
        'fichier_nom_original', 'statut', 'commentaire_encadreur',
        'date_soumission', 'date_correction',
    ];

    protected $casts = [
        'date_soumission' => 'datetime',
        'date_correction' => 'datetime',
    ];

    public function stage(): BelongsTo
    {
        return $this->belongsTo(Stage::class);
    }

    public function etudiant(): BelongsTo
    {
        return $this->belongsTo(Etudiant::class);
    }

    public function estIntermediaire(): bool
    {
        return $this->type_rapport === 'intermediaire';
    }

    public function estFinal(): bool
    {
        return $this->type_rapport === 'final';
    }

    public function estValide(): bool
    {
        return $this->statut === 'valide';
    }

    public function estCorrige(): bool
    {
        return $this->statut === 'corrige';
    }

    public function peutEtreModifie(): bool
    {
        return $this->statut === 'soumis' || $this->statut === 'corrige';
    }
}
