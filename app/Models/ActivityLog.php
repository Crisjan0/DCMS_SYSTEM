<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only audit trail powering the dashboard's staff activity feed.
 * Every clinic action (assignments, charting, billing…) writes one row.
 */
class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'description',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Write one audit row for the currently authenticated user.
     */
    public static function record(string $action, string $description, ?Model $subject = null, array $meta = []): self
    {
        return static::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'subject_type' => $subject !== null ? class_basename($subject) : null,
            'subject_id' => $subject?->getKey(),
            'description' => $description,
            'meta' => $meta !== [] ? $meta : null,
        ]);
    }

    /**
     * Icon shown next to the activity in the dashboard feed.
     */
    public function icon(): string
    {
        return match ($this->action) {
            'appointment_booked', 'appointment_assigned' => '📅',
            'appointment_rescheduled' => '🔀',
            'appointment_cancelled', 'appointment_no_show' => '🚫',
            'appointment_completed' => '✅',
            'treatment_charted' => '🩺',
            'treatment_updated', 'treatment_deleted' => '✏️',
            'tooth_chart_updated' => '🦷',
            'medical_history_updated' => '📋',
            'invoice_created', 'invoice_voided' => '🧾',
            'payment_recorded' => '💰',
            default => '•',
        };
    }
}
