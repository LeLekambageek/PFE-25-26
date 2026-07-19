<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Entreprise extends Model
{
    use HasFactory;

    protected $fillable = [
        'raison_sociale', 'secteur_activite',
        'contact_nom', 'contact_email', 'contact_telephone',
    ];

    public function stages(): HasMany
    {
        return $this->hasMany(Stage::class);
    }
}