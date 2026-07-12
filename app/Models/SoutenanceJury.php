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
    ];

    protected function casts(): array
    {
        return [
            'convocation_envoyee' => 'boolean',
            'convoque_a' => 'datetime',
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
}
