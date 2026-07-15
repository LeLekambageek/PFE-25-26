<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'type_document', 'titre', 'auteur', 'annee', 'mention',
        'mots_cles', 'fichier_path', 'archive_par_id', 'memoire_id', 'stage_id',
    ];

    public function archivePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'archive_par_id');
    }

    public function memoire(): BelongsTo
    {
        return $this->belongsTo(Memoire::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(Stage::class);
    }
}