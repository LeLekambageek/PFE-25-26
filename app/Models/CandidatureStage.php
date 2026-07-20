<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidatureStage extends Model
{
    use HasFactory;

    protected $table = 'candidatures_stage';

   protected $fillable = [
    'etudiant_id', 'offre_id', 'entreprise_id', 'titre_poste', 'description',
    'cv_path', 'lettre_motivation_path', 'statut', 'commentaire_admin',
    'date_candidature', 'date_reponse', 'stage_id',
];

    protected $casts = [
        'date_candidature' => 'datetime',
        'date_reponse' => 'datetime',
    ];

    public function etudiant(): BelongsTo
    {
        return $this->belongsTo(Etudiant::class);
    }

    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class);
    }

    public function offre(): BelongsTo
{
    return $this->belongsTo(OffreStage::class, 'offre_id');
}

    public function stage(): BelongsTo
    {
        return $this->belongsTo(Stage::class);
    }

    public function estRetenue(): bool
    {
        return $this->statut === 'retenue';
    }

    public function estEnAttente(): bool
    {
        return $this->statut === 'en_attente';
    }

    public function peutEtreAffectee(): bool
    {
        return $this->statut === 'retenue' && $this->stage_id === null;
    }

    
}
