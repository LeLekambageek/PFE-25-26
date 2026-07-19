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

    public function stages()
    {
        return $this->hasMany(Stage::class);
    }
}