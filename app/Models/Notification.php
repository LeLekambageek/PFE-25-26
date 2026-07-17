<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'type', 'titre', 'message', 'data', 'statut',
        'date_creation', 'date_lecture', 'notifiable_type', 'notifiable_id',
    ];

    protected $casts = [
        'data' => 'array',
        'date_creation' => 'datetime',
        'date_lecture' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    public function marquerCommeLue(): void
    {
        $this->update([
            'statut' => 'lue',
            'date_lecture' => now(),
        ]);
    }

    public function estNonLue(): bool
    {
        return $this->statut === 'non_lue';
    }

    public function scopeNonLues($query)
    {
        return $query->where('statut', 'non_lue');
    }

    public function scopePourUtilisateur($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeDeType($query, $type)
    {
        return $query->where('type', $type);
    }
}
