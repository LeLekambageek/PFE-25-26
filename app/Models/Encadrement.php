<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Encadrement extends Model
{
    use HasFactory;

    protected $fillable = [
        'etudiant_id', 'enseignant_id', 'type', 'statut',
    ];

    public function etudiant(): BelongsTo
    {
        return $this->belongsTo(Etudiant::class);
    }

    public function enseignant(): BelongsTo
    {
        return $this->belongsTo(Enseignant::class);
    }

    public function rendezVous(): HasMany
    {
        return $this->hasMany(EncadrementRendezVous::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(EncadrementEntry::class);
    }

    public function estActif(): bool
    {
        return $this->statut === 'actif';
    }
}