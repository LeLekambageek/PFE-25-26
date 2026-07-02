<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Etudiant extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'matricule', 'filiere', 'niveau',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Un étudiant peut avoir plusieurs stages au fil de sa scolarité mais seulement un stage actif à la fois Cette relation permet de récupérer tous les stages d'un étudiant
    public function stages()
    {
        return $this->hasMany(Stage::class);
    }
}