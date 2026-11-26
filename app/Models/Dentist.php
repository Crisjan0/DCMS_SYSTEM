<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dentist extends Model
{
    protected $fillable = [
        'user_id',
        'lastname',
        'firstname',
        'middlename',
        'date_of_birth',
        'mobile_number',
        'gender',
        'license_number',
        'specialty',
        'profile_picture',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function fullname(): string
    {
        // Collapse double spaces that appear when middlename is null.
        return trim(preg_replace('/\s+/', ' ', $this->firstname.' '.$this->middlename.' '.$this->lastname));
    }
}
