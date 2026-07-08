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
    ];

    protected function casts(): array
    {
        return [
            'date_proposition' => 'datetime',
            'date_validation' => 'datetime',
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
}
