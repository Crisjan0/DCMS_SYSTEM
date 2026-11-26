<?php

namespace App\Services;

use App\Models\Dentist;
use App\Models\Patient;
use App\Models\User;
use App\Notifications\ClinicNotification;
use Illuminate\Database\Eloquent\Builder;

/**
 * Single entry point for firing clinic notifications. Recipient
 * resolution is role-based and persisted in Laravel's notifications table:
 *
 * - admins   → users.is_admin
 * - staff    → users linked to a patients profile with role "staff"
 * - dentists → the user linked to a dentists profile
 * - patients → the user linked to a patients profile
 *
 * Silent no-op when a profile has no linked user account.
 */
class NotificationService
{
    /**
     * Publish a notification to one user or an iterable of users.
     *
     * @param  array<string, mixed>  $extra
     */
    public function send(User|iterable $users, string $type, string $title, string $body, ?string $link = null, array $extra = []): void
    {
        $users = $users instanceof User ? [$users] : $users;

        foreach ($users as $user) {
            if (! $user instanceof User) {
                continue;
            }

            $user->notify(new ClinicNotification($type, $title, $body, $link, $extra));
        }
    }

    /**
     * Notify every admin account.
     *
     * @param  array<string, mixed>  $extra
     */
    public function toAdmins(string $type, string $title, string $body, ?string $link = null, array $extra = []): void
    {
        $this->send(User::query()->where('is_admin', true)->get(), $type, $title, $body, $link, $extra);
    }

    /**
     * Notify every staff account.
     *
     * @param  array<string, mixed>  $extra
     */
    public function toStaff(string $type, string $title, string $body, ?string $link = null, array $extra = []): void
    {
        $this->send(self::staffQuery()->get(), $type, $title, $body, $link, $extra);
    }

    /**
     * The clinic back office: every admin and staff account.
     *
     * @param  array<string, mixed>  $extra
     */
    public function toClinic(string $type, string $title, string $body, ?string $link = null, array $extra = []): void
    {
        $this->send(
            self::adminOrStaffQuery()->get(),
            $type,
            $title,
            $body,
            $link,
            $extra,
        );
    }

    /**
     * Notify the user account linked to a patient profile.
     *
     * @param  array<string, mixed>  $extra
     */
    public function toPatient(?Patient $patient, string $type, string $title, string $body, ?string $link = null, array $extra = []): void
    {
        if ($patient?->user) {
            $this->send($patient->user, $type, $title, $body, $link, $extra);
        }
    }

    /**
     * Notify the user account linked to a dentist profile.
     *
     * @param  array<string, mixed>  $extra
     */
    public function toDentist(?Dentist $dentist, string $type, string $title, string $body, ?string $link = null, array $extra = []): void
    {
        if ($dentist?->user) {
            $this->send($dentist->user, $type, $title, $body, $link, $extra);
        }
    }

    /**
     * Whether a reminder carrying this dedupe key was already sent
     * to the user earlier today (guards the scheduled reminder run).
     */
    public function alreadySent(User $user, string $dedupeKey): bool
    {
        return $user->notifications()
            ->where('type', ClinicNotification::class)
            ->whereDate('created_at', today())
            ->get(['id', 'data'])
            ->contains(fn ($notification) => ($notification->data['dedupe_key'] ?? null) === $dedupeKey);
    }

    private static function adminOrStaffQuery(): Builder
    {
        return User::query()->where(function (Builder $query): void {
            $query->where('is_admin', true)
                ->orWhereHas('patient', fn (Builder $patient) => $patient->where('role', 'staff'));
        });
    }

    private static function staffQuery(): Builder
    {
        return User::query()->whereHas(
            'patient',
            fn (Builder $patient) => $patient->where('role', 'staff'),
        );
    }
}
