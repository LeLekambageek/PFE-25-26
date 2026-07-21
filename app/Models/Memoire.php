<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Memoire extends Model
{
    use HasFactory;

    protected $fillable = [
        'titre', 'description', 'etudiant_id', 'encadreur_id', 'propose_par_id',
        'statut', 'date_proposition', 'date_validation', 'commentaire_validation',
        'eligible_soutenance', 'date_eligibilite_soutenance',
    ];

    protected function casts(): array
    {
        return [
            'date_proposition' => 'datetime',
            'date_validation' => 'datetime',
            'eligible_soutenance' => 'boolean',
            'date_eligibilite_soutenance' => 'datetime',
        ];
    }

    public function etudiant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'etudiant_id');
    }

    public function encadreur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'encadreur_id');
    }

    public function proposePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'propose_par_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(MemoireVersion::class)->orderBy('numero_version');
    }

    public function derniereVersion(): HasOne
    {
        return $this->hasOne(MemoireVersion::class)->latestOfMany();
    }

    public function soutenance(): HasOne
    {
        return $this->hasOne(Soutenance::class);
    }

public function estProposeParEtudiant(): bool
    {
        return $this->propose_par_id === $this->etudiant_id;
    }

    public function peutDemanderSoutenance(): bool
    {
        return $this->statut === 'valide_final'
            && $this->eligible_soutenance === true
            && ! $this->soutenance()->exists();
    }

    public function estVerrouillePourSoutenance(): bool
    {
        $soutenance = $this->soutenance;
        if (! $soutenance || ! $soutenance->date_soutenance) {
            return false;
        }

        return now()->greaterThanOrEqualTo($soutenance->date_soutenance->copy()->startOfDay()->subDays(5));
    }

    public function encadrement()
    {
        return $this->morphOne(Encadrement::class, 'encadrable');
    }
}