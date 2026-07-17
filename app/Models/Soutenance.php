<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Soutenance extends Model
{
    use HasFactory;

    protected $fillable = [
        'memoire_id', 'planifiee_par_id', 'date_soutenance', 'heure_debut', 'salle',
        'statut', 'note_finale', 'mention', 'pv_path', 'resultats_publies',
    ];

    protected function casts(): array
    {
        return [
            'date_soutenance' => 'date',
            'resultats_publies' => 'boolean',
            'note_finale' => 'decimal:2',
        ];
    }

    public function memoire(): BelongsTo
    {
        return $this->belongsTo(Memoire::class);
    }

    public function planifieePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'planifiee_par_id');
    }

    public function jury(): HasMany
    {
        return $this->hasMany(SoutenanceJury::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(SoutenanceNote::class);
    }

    public function procesVerbaux(): HasMany
    {
        return $this->hasMany(ProcesVerbal::class);
    }

    public function estMembreDuJury(User $user): bool
    {
        return $this->jury()->where('user_id', $user->id)->exists();
    }

    public function estTerminee(): bool
    {
        return $this->statut === 'terminee' || $this->resultats_publies;
    }

    public function resultatsSontPublies(): bool
    {
        return (bool) $this->resultats_publies;
    }

    public function tousLesJuryOntNote(): bool
    {
        return $this->jury()->exists() && $this->jury()->where('notes_validees', false)->doesntExist();
    }
}
