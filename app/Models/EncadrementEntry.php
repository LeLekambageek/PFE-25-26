<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EncadrementEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'encadrement_id', 'auteur_id', 'contenu',
    ];

    public function encadrement(): BelongsTo
    {
        return $this->belongsTo(Encadrement::class);
    }

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'auteur_id');
    }
}