<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OffreStage extends Model
{
    use HasFactory;

    protected $table = 'offres_stage';

    protected $fillable = [
        'entreprise_id', 'titre', 'description', 'competences_requises',
        'date_debut_souhaitee', 'date_fin_souhaitee', 'statut', 'publiee_par_id',
    ];

    protected $casts = [
        'date_debut_souhaitee' => 'date',
        'date_fin_souhaitee' => 'date',
    ];

    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class);
    }

    public function publieePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'publiee_par_id');
    }

    public function candidatures(): HasMany
    {
        return $this->hasMany(CandidatureStage::class, 'offre_id');
    }

    public function estOuverte(): bool
    {
        return $this->statut === 'ouverte';
    }
}
