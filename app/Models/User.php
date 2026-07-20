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

#[Fillable(['name', 'email', 'password', 'must_change_password', 'date_debut_acces', 'date_expiration'])]
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
            'must_change_password' => 'boolean',
            'date_debut_acces' => 'datetime',
            'date_expiration' => 'datetime',
        ];
    }

    /**
     * Compte expiré (borne de fin de période de validité, utilisée pour les
     * comptes jury) : bloque l'accès via EnsureAccountIsActive.
     */
    public function estExpire(): bool
    {
        return $this->date_expiration !== null && now()->greaterThan($this->date_expiration);
    }

    /**
     * Compte pas encore actif (borne de début de période de validité).
     */
    public function accesPasEncoreActif(): bool
    {
        return $this->date_debut_acces !== null && now()->lessThan($this->date_debut_acces);
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