<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
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
    public function patient(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Patient::class);
    }

    /**
     * The dentist profile linked to this user account.
     */
    public function dentist(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Dentist::class);
    }

    /**
     * Check the user's clinic role.
     *
     * Admin is a flag on the user account itself (`users.is_admin`),
     * while staff/patient roles are stored on the linked patient profile.
     */
    public function hasRole(string ...$roles): bool
    {
        if (in_array('admin', $roles, true) && $this->is_admin) {
            return true;
        }

        $role = $this->patient?->role;

        return $role !== null && in_array($role, $roles, true);
    }
}
