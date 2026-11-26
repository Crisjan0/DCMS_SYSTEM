<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyClinicStat extends Model
{
    protected $fillable = [
        'date',
        'total_visits',
        'walk_ins',
        'new_patients',
        'no_shows',
        'cancellations',
    ];

    protected function casts(): array
    {
        // Serialize as a bare Y-m-d string on write, so the upsert lookup in
        // StatsAggregator (which binds '2024-01-15') matches the stored row.
        // The default 'date' cast writes '2024-01-15 00:00:00' on SQLite,
        // which never matches the lookup → duplicate insert → UNIQUE crash.
        return [
            'date' => 'date:Y-m-d',
        ];
    }
}
