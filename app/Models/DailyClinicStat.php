<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyClinicStat extends Model
{
    protected $fillable = [
        'date',
        'total_visits',
        'new_patients',
        'no_shows',
        'cancellations',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }
}
