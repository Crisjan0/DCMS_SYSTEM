<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    /** Services the clinic offers — used by the booking form and the seeder. */
    public const SERVICES = [
        'Consultation',
        'Oral Prophylaxis (Cleaning)',
        'Tooth Extraction',
        'Composite Filling',
        'Root Canal Treatment',
        'Braces Adjustment',
        'Teeth Whitening',
        'Wisdom Tooth Removal',
        'Denture Fitting',
    ];

    /** Suggested cash prices (₱) per service — used by the billing form. */
    public const SERVICE_PRICES = [
        'Consultation' => 500,
        'Oral Prophylaxis (Cleaning)' => 1200,
        'Tooth Extraction' => 1500,
        'Composite Filling' => 1800,
        'Root Canal Treatment' => 6500,
        'Braces Adjustment' => 2500,
        'Teeth Whitening' => 5500,
        'Wisdom Tooth Removal' => 8000,
        'Denture Fitting' => 12000,
    ];

    /** Clinic operating hours (closed Sundays). */
    public const CLINIC_OPEN_TIME = '09:00';

    public const CLINIC_LAST_SLOT_TIME = '16:30';

    public const SLOT_MINUTES = 30;

    /** Statuses that still hold a time slot. */
    public const ACTIVE_STATUSES = ['pending', 'confirmed'];

    protected $fillable = [
        'patient_id',
        'dentist_id',
        'scheduled_date',
        'scheduled_time',
        'service',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'scheduled_time' => 'datetime:H:i',
        ];
    }

    /**
     * Always display the time as HH:MM (e.g. 12:30) — the datetime cast
     * alone would echo the full "Y-m-d H:i" string.
     */
    protected function scheduledTime(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => filled($value) ? Carbon::parse($value)->format('H:i') : null,
        );
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function dentist(): BelongsTo
    {
        return $this->belongsTo(Dentist::class, 'dentist_id');
    }

    /**
     * 30-minute booking slots from opening time until the last slot.
     *
     * @return list<string> e.g. ['09:00', '09:30', ... '16:30']
     */
    public static function timeSlots(): array
    {
        $cursor = Carbon::parse(self::CLINIC_OPEN_TIME);
        $slots = [];

        while ($cursor->format('H:i') <= self::CLINIC_LAST_SLOT_TIME) {
            $slots[] = $cursor->format('H:i');
            $cursor->addMinutes(self::SLOT_MINUTES);
        }

        return $slots;
    }

    /**
     * Scope appointments that occupy the given slot (cancelled ones free it up).
     */
    public function scopeConflictingAt(Builder $query, string $date, string $time): Builder
    {
        return $query
            ->whereDate('scheduled_date', $date)
            ->where('scheduled_time', $time.':00')
            ->whereNotIn('status', ['cancelled', 'no_show']);
    }

    /**
     * Patient/staff may cancel while pending or confirmed and the date hasn't passed.
     */
    public function isCancellable(): bool
    {
        return in_array($this->status, ['pending', 'confirmed'], true)
            && $this->scheduled_date->greaterThanOrEqualTo(today());
    }
}
