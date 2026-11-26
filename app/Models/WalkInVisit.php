<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A walk-in visit: an unbooked patient the clinic served on the spot.
 * Counted into daily clinic stats so forecasting reflects real volume.
 */
class WalkInVisit extends Model
{
    protected $fillable = [
        'patient_id',
        'dentist_id',
        'service',
        'visit_date',
        'count',
        'notes',
        'handled_by',
    ];

    protected function casts(): array
    {
        return [
            'visit_date' => 'date',
            'count' => 'integer',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function dentist(): BelongsTo
    {
        return $this->belongsTo(Dentist::class);
    }

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
