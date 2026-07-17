<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Etudiant extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'matricule', 'filiere', 'niveau',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Un étudiant peut avoir plusieurs stages au fil de sa scolarité mais seulement un stage actif à la fois Cette relation permet de récupérer tous les stages d'un étudiant
    public function stages()
    {
        return $this->hasMany(Stage::class);
    }

    public function memoires(): HasMany
    {
        return $this->hasMany(Memoire::class, 'etudiant_id', 'user_id');
    }

    public function encadrements(): HasMany
    {
        return $this->hasMany(Encadrement::class);
    }

    public function candidatures(): HasMany
    {
        return $this->hasMany(CandidatureStage::class);
    }

    public function rapports(): HasMany
    {
        return $this->hasMany(RapportStage::class);
    }

    public function stageActif(): ?Stage
    {
        return $this->stages()->whereIn('statut', ['valide', 'en_cours'])->latest()->first();
    }

    public function peutPostulerCandidature(): bool
    {
        return ! $this->stages()->whereIn('statut', ['valide', 'en_cours'])->exists();
    }
}