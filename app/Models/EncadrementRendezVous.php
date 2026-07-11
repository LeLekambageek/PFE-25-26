<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EncadrementRendezVous extends Model
{
    use HasFactory;

    protected $table = 'encadrement_rendez_vous';

    protected $fillable = [
        'encadrement_id', 'date_prevue', 'sujet', 'statut',
    ];

    protected $casts = [
        'date_prevue' => 'datetime',
    ];

    public function encadrement(): BelongsTo
    {
        return $this->belongsTo(Encadrement::class);
    }
}