<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MemoireVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'memoire_id', 'soumis_par_id', 'numero_version',
        'fichier_path', 'fichier_nom_original', 'statut',
        'pourcentage_avancement', 'verrouille', 'date_verrouillage',
        'annotations', 'commentaires_encadreur', 'recommandations',
    ];

    protected function casts(): array
    {
        return [
            'pourcentage_avancement' => 'integer',
            'verrouille' => 'boolean',
            'date_verrouillage' => 'datetime',
        ];
    }

    public function memoire(): BelongsTo
    {
        return $this->belongsTo(Memoire::class);
    }

    public function soumisPar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'soumis_par_id');
    }

    public function corrections(): HasMany
    {
        return $this->hasMany(MemoireCorrection::class);
    }

    public function estFinale(): bool
    {
        return $this->numero_version === 'finale';
    }

    public function mettreAJourAvancement(int $pourcentage): void
    {
        $this->update(['pourcentage_avancement' => $pourcentage]);
    }
}
