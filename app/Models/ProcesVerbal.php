<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcesVerbal extends Model
{
    use HasFactory;

    protected $table = 'proces_verbaux';

    protected $fillable = [
        'soutenance_id', 'genere_par_id', 'fichier', 'date_generation',
        'commentaires', 'est_signe', 'date_signature', 'signatures',
    ];

    protected function casts(): array
    {
        return [
            'date_generation' => 'datetime',
            'date_signature' => 'datetime',
            'est_signe' => 'boolean',
            'signatures' => 'array',
        ];
    }

    public function soutenance(): BelongsTo
    {
        return $this->belongsTo(Soutenance::class);
    }

    public function generePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'genere_par_id');
    }
}
