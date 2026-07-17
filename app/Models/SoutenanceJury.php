<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SoutenanceJury extends Model
{
    use HasFactory;

    protected $table = 'soutenance_jury';

    protected $fillable = [
        'soutenance_id', 'user_id', 'role_jury', 'convocation_envoyee', 'convoque_a',
        'date_debut_acces', 'date_fin_acces', 'actif', 'date_desactivation',
        'notes_validees', 'notes_validees_a',
    ];

    protected function casts(): array
    {
        return [
            'convocation_envoyee' => 'boolean',
            'convoque_a' => 'datetime',
            'date_debut_acces' => 'datetime',
            'date_fin_acces' => 'datetime',
            'date_desactivation' => 'datetime',
            'actif' => 'boolean',
            'notes_validees' => 'boolean',
            'notes_validees_a' => 'datetime',
        ];
    }

    public function soutenance(): BelongsTo
    {
        return $this->belongsTo(Soutenance::class);
    }

    public function membre(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function estActif(): bool
    {
        if (! $this->actif) {
            return false;
        }
        if ($this->date_debut_acces && now()->lt($this->date_debut_acces)) {
            return false;
        }
        if ($this->date_fin_acces && now()->gt($this->date_fin_acces)) {
            return false;
        }

        return true;
    }

    public function peutNoter(): bool
    {
        return $this->estActif() && ! $this->notes_validees && ! $this->soutenance->estTerminee();
    }

    public function validerNotes(): void
    {
        $this->update(['notes_validees' => true, 'notes_validees_a' => now()]);
    }

    public function reactiver(?string $dateFinAcces = null): void
    {
        $this->update([
            'actif' => true,
            'date_desactivation' => null,
            'date_debut_acces' => now(),
            'date_fin_acces' => $dateFinAcces ?? $this->soutenance->date_soutenance?->copy()->addDay(),
        ]);
    }
}
