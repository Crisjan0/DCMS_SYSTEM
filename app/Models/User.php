<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

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
            'is_admin' => 'boolean',
        ];
    }

    /**
     * The clinic profile linked to this user account.
     */
    public function patient(): HasOne
    {
        return $this->hasOne(Patient::class);
    }

    /**
     * The dentist profile linked to this user account.
     */
    public function dentist(): HasOne
    {
        return $this->hasOne(Dentist::class);
    }

    /**
     * Check the user's clinic role.
     *
     * Admin is a flag on the user account itself (`users.is_admin`),
     * staff/patient roles are stored on the linked patient profile,
     * and dentists are identified by their linked dentists profile.
     */
    public function hasRole(string ...$roles): bool
    {
        if (in_array('admin', $roles, true) && $this->is_admin) {
            return true;
        }

        if (in_array('dentist', $roles, true) && $this->dentist()->exists()) {
            return true;
        }

        $role = $this->patient?->role;

        return $role !== null && in_array($role, $roles, true);
    }
}
