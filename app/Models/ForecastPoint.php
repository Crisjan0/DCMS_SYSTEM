<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ForecastPoint extends Model
{
    protected $fillable = [
        'forecast_run_id',
        'date',
        'predicted',
        'lower',
        'upper',
        'is_peak',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'predicted' => 'float',
            'lower' => 'float',
            'upper' => 'float',
            'is_peak' => 'boolean',
        ];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(ForecastRun::class, 'forecast_run_id');
    }
}
