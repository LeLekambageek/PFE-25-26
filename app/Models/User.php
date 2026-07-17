<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasApiTokens, HasRoles;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function etudiant(): HasOne
    {
        return $this->hasOne(Etudiant::class);
    }

    public function enseignant(): HasOne
    {
        return $this->hasOne(Enseignant::class);
    }

    public function memoires(): HasMany
    {
        return $this->hasMany(Memoire::class, 'etudiant_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class)->latest('date_creation');
    }

    public function notificationsNonLues(): HasMany
    {
        return $this->notifications()->where('statut', 'non_lue');
    }

    public function soutenanceJury(): HasMany
    {
        return $this->hasMany(SoutenanceJury::class, 'user_id');
    }
}