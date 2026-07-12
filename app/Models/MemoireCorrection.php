<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemoireCorrection extends Model
{
    use HasFactory;

    protected $fillable = [
        'memoire_version_id', 'auteur_id', 'commentaire', 'type_correction',
    ];

    public function version(): BelongsTo
    {
        return $this->belongsTo(MemoireVersion::class, 'memoire_version_id');
    }

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'auteur_id');
    }
}
