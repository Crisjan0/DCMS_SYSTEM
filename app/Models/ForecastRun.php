<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ForecastRun extends Model
{
    protected $fillable = [
        'metric',
        'algorithm',
        'horizon_days',
        'history_days',
        'mape',
        'ran_at',
    ];

    protected function casts(): array
    {
        return [
            'mape' => 'float',
            'ran_at' => 'datetime',
        ];
    }

    public function points(): HasMany
    {
        return $this->hasMany(ForecastPoint::class);
    }
}
