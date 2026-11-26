<?php

namespace App\Notifications;

/**
 * Machine-readable payload types persisted in the notifications table's
 * `data.type` JSON key. The dotsegments group related events so the
 * Livewire bell/page can filter them (e.g. "appointment.*").
 */
final class NotificationType
{
    // ── Appointments ─────────────────────────────────────────────
    public const APPOINTMENT_BOOKED = 'appointment.booked';

    public const APPOINTMENT_ASSIGNED = 'appointment.assigned';

    public const APPOINTMENT_CONFIRMED = 'appointment.confirmed';

    public const APPOINTMENT_RESCHEDULED = 'appointment.rescheduled';

    public const APPOINTMENT_CANCELLED = 'appointment.cancelled';

    public const APPOINTMENT_COMPLETED = 'appointment.completed';

    public const APPOINTMENT_NO_SHOW = 'appointment.no_show';

    public const APPOINTMENT_REMINDER = 'appointment.reminder';

    // ── Billing ──────────────────────────────────────────────────
    public const INVOICE_CREATED = 'invoice.created';

    public const INVOICE_VOIDED = 'invoice.voided';

    public const PAYMENT_RECORDED = 'payment.recorded';

    // ── Patient records ─────────────────────────────────────────
    public const TREATMENT_ADDED = 'treatment.added';

    public const TREATMENT_UPDATED = 'treatment.updated';

    public const TOOTH_CHART_UPDATED = 'tooth_chart.updated';

    public const MEDICAL_HISTORY_UPDATED = 'medical_history.updated';

    // ── Clinic-wide messages ────────────────────────────────────
    public const SYSTEM_BROADCAST = 'system.broadcast';

    /**
     * Notification page filter groups → payload-type prefixes.
     *
     * @var array<string, list<string>>
     */
    public const GROUPS = [
        'appointments' => ['appointment.'],
        'billing' => ['invoice.', 'payment.'],
        'records' => ['treatment.', 'tooth_chart.', 'medical_history.'],
        'system' => ['system.'],
    ];

    /** Display labels for the filter groups above. */
    public const GROUP_LABELS = [
        'appointments' => 'Appointments',
        'billing' => 'Billing',
        'records' => 'Health Records',
        'system' => 'System',
    ];

    /**
     * Nav-icon key (x-nav-icon) used to render the payload type.
     */
    public static function iconFor(string $type): string
    {
        return match (explode('.', $type)[0]) {
            'appointment' => 'calendar',
            'invoice', 'payment' => 'invoice',
            'treatment', 'tooth_chart' => 'tooth',
            'medical_history' => 'heart',
            default => 'bell',
        };
    }

    /**
     * Type prefixes behind a filter group key.
     *
     * @return list<string>
     */
    public static function groupPrefixes(string $group): array
    {
        return self::GROUPS[$group] ?? [];
    }
}
