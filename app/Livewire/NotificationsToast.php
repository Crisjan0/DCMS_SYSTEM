<?php

namespace App\Livewire;

use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Global toast stack (Livewire-only — no browser JS state).
 *
 * - Session flash messages are picked up once on mount, so every
 *   classic controller redirect gets a toast for free.
 * - Any component can fire a toast with:
 *   $this->dispatch('notify', type: 'success', message: '...').
 * - Auto-dismissed through a poll that only runs while toasts exist.
 */
class NotificationsToast extends Component
{
    /** @var list<array{id: string, type: string, message: string, expires_at: int}> */
    public array $toasts = [];

    public function mount(): void
    {
        foreach (['success', 'error', 'status', 'info'] as $key) {
            $message = session()->pull($key);

            if (filled($message)) {
                // Breeze flashes 'status' for profile/auth updates.
                $this->push($key === 'status' ? 'info' : $key, (string) $message);
            }
        }
    }

    #[On('notify')]
    public function push(string $type = 'success', string $message = ''): void
    {
        if (blank($message)) {
            return;
        }

        $this->toasts[] = [
            'id' => (string) Str::uuid(),
            'type' => in_array($type, ['success', 'error', 'info'], true) ? $type : 'info',
            'message' => $message,
            'expires_at' => now()->addSeconds(5)->getTimestamp(),
        ];
    }

    public function dismiss(string $id): void
    {
        $this->toasts = collect($this->toasts)
            ->reject(fn (array $toast) => $toast['id'] === $id)
            ->values()
            ->all();
    }

    public function render()
    {
        // Prune expired toasts — the stack view carries a poll that
        // runs only while at least one toast exists.
        $this->toasts = collect($this->toasts)
            ->reject(fn (array $toast) => $toast['expires_at'] <= now()->getTimestamp())
            ->values()
            ->all();

        return view('livewire.notifications.toast');
    }
}
