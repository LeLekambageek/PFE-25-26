<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SoutenanceNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'soutenance_id', 'jury_id', 'critere', 'note', 'commentaire',
    ];

    protected function casts(): array
    {
        return [
            'note' => 'decimal:2',
        ];
    }

    public function soutenance(): BelongsTo
    {
        return $this->belongsTo(Soutenance::class);
    }

    public function jury(): BelongsTo
    {
        return $this->belongsTo(User::class, 'jury_id');
    }
}
