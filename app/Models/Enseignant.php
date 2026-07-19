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

    public function stagesEncadres(): HasMany
    {
        return $this->hasMany(Stage::class, 'encadreur_id');
    }
}