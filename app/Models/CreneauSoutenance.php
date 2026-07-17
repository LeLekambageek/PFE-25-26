<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreneauSoutenance extends Model
{
    use HasFactory;

    protected $table = 'creneaux_soutenance';

    protected $fillable = [
        'planifie_par_id', 'date_disponible', 'heure_debut', 'heure_fin',
        'salle', 'statut', 'soutenance_id', 'memoire_id', 'date_reservation',
    ];

    protected $casts = [
        'date_disponible' => 'date',
        'date_reservation' => 'datetime',
    ];

    public function planifiePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'planifie_par_id');
    }

    public function soutenance(): BelongsTo
    {
        return $this->belongsTo(Soutenance::class);
    }

    public function memoire(): BelongsTo
    {
        return $this->belongsTo(Memoire::class);
    }

    public function estDisponible(): bool
    {
        return $this->statut === 'disponible';
    }

    public function estReserve(): bool
    {
        return $this->statut === 'reserve';
    }

    public function peutEtreReserve(): bool
    {
        return $this->statut === 'disponible' && $this->date_disponible > now()->addDays(5);
    }

    public function reserver(int $memoireId): void
    {
        $this->update([
            'statut' => 'reserve',
            'memoire_id' => $memoireId,
            'date_reservation' => now(),
        ]);
    }

    public function annuler(): void
    {
        $this->update([
            'statut' => 'disponible',
            'soutenance_id' => null,
            'memoire_id' => null,
            'date_reservation' => null,
        ]);
    }
}
