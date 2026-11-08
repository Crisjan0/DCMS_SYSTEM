<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicalHistory extends Model
{
    /** Condition flags shown on the health declaration. */
    public const CONDITIONS = [
        'hypertension' => 'Hypertension / High blood pressure',
        'heart_disease' => 'Heart disease',
        'diabetes' => 'Diabetes',
        'asthma' => 'Asthma',
        'kidney_disease' => 'Kidney disease',
        'liver_disease' => 'Liver disease',
        'blood_disorder' => 'Blood disorder',
        'epilepsy' => 'Epilepsy / Seizures',
        'pregnant' => 'Currently pregnant',
        'smoker' => 'Smoker',
    ];

    protected $fillable = [
        'patient_id',
        'allergies',
        'medications',
        'hypertension',
        'heart_disease',
        'diabetes',
        'asthma',
        'kidney_disease',
        'liver_disease',
        'blood_disorder',
        'epilepsy',
        'pregnant',
        'smoker',
        'other_conditions',
        'medical_notes',
        'updated_by',
        'declared_at',
    ];

    protected function casts(): array
    {
        return [
            'hypertension' => 'boolean',
            'heart_disease' => 'boolean',
            'diabetes' => 'boolean',
            'asthma' => 'boolean',
            'kidney_disease' => 'boolean',
            'liver_disease' => 'boolean',
            'blood_disorder' => 'boolean',
            'epilepsy' => 'boolean',
            'pregnant' => 'boolean',
            'smoker' => 'boolean',
            'declared_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Only the condition flags that are set to true.
     *
     * @return list<string>
     */
    public function activeConditions(): array
    {
        return collect(array_keys(self::CONDITIONS))
            ->filter(fn (string $flag) => $this->{$flag})
            ->values()
            ->all();
    }
}
