<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Enseignant extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'specialite', 'capacite_encadrement',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Un enseignant peut encadrer plusieurs stages à la fois,
    public function stagesEncadres(): HasMany
    {
        return $this->hasMany(Stage::class, 'encadreur_id');
    }

    public function encadrements(): HasMany
    {
        return $this->hasMany(Encadrement::class, 'enseignant_id');
    }

    public function etudiantsEncadres(): \Illuminate\Support\Collection
    {
        $etudiantIds = $this->encadrements()->where('statut', 'actif')->pluck('etudiant_id')->unique();

        return Etudiant::whereIn('id', $etudiantIds)->get();
    }

    public function capaciteAtteinte(): bool
    {
        return $this->encadrements()->where('statut', 'actif')->count() >= $this->capacite_encadrement;
    }
}