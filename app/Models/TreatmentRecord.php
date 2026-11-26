<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A clinical procedure performed on a patient (SOAP-style chart entry),
 * optionally linked to the appointment that produced it.
 */
class TreatmentRecord extends Model
{
    protected $fillable = [
        'patient_id',
        'dentist_id',
        'appointment_id',
        'treatment_date',
        'tooth_number',
        'procedure',
        'diagnosis',
        'treatment_notes',
        'follow_up_date',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'treatment_date' => 'date',
            'follow_up_date' => 'date',
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

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }
}
