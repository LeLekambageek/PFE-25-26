<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stage extends Model
{
    use HasFactory;

    protected $fillable = [
        'etudiant_id', 'entreprise_id', 'encadreur_id',
        'titre', 'description', 'date_debut', 'date_fin',
        'statut', 'convention_path', 'attestation_path',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
    ];

    public function etudiant(): BelongsTo
    {
        return $this->belongsTo(Etudiant::class);
    }

    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class);
    }

    public function encadreur(): BelongsTo
    {
        return $this->belongsTo(Enseignant::class, 'encadreur_id');
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(StageJournalEntry::class);
    }

    public function peutEtreValide(): bool
    {
        return $this->statut === 'en_attente';
    }
}