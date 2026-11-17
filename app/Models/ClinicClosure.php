<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Clinic closure calendar: public holidays, branch closures and
 * maintenance days. Booking is blocked on these dates, and the
 * forecasting module treats them as structural zeros.
 */
class ClinicClosure extends Model
{
    public const TYPES = ['holiday', 'closure', 'maintenance'];

    protected $fillable = ['date', 'name', 'type'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    /**
     * Is the clinic closed on the given date?
     */
    public static function isClosed(string|Carbon $date): bool
    {
        $date = $date instanceof Carbon ? $date->toDateString() : (string) $date;

        return static::query()->where('date', $date)->exists();
    }

    /**
     * All closure dates as Y-m-d strings (used to zero forecast points).
     *
     * @return list<string>
     */
    public static function datesBetween(string $start, string $end): array
    {
        return static::query()
            ->whereBetween('date', [$start, $end])
            ->pluck('date')
            ->map(fn ($date) => $date instanceof Carbon ? $date->toDateString() : (string) $date)
            ->all();
    }

    public function scopeUpcoming($query)
    {
        return $query->where('date', '>=', today())->orderBy('date');
    }
}
