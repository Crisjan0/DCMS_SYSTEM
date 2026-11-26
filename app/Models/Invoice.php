<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A patient invoice: one or more line items (often treatment records),
 * an optional discount, and the payments received against it.
 *
 * Total math is always recomputed from the items + payments via recalculate().
 */
class Invoice extends Model
{
    public const STATUSES = ['unpaid', 'partial', 'paid', 'void'];

    protected $fillable = [
        'invoice_no',
        'patient_id',
        'issued_by',
        'subtotal',
        'discount',
        'total',
        'paid_amount',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_amount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        // Sequential invoice number: INV-<year>-<id offset>. Computed
        // before insert since the column is NOT NULL + UNIQUE.
        static::creating(function (self $invoice) {
            if (blank($invoice->invoice_no)) {
                $next = (int) self::query()->max('id') + 1;

                $invoice->invoice_no = 'INV-'.now()->year.'-'.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
            }
        });
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Outstanding amount (zero for void invoices).
     */
    public function balance(): float
    {
        if ($this->status === 'void') {
            return 0.0;
        }

        return round(max(0, (float) $this->total - (float) $this->paid_amount), 2);
    }

    /**
     * Recompute subtotal / total / paid_amount / status from the
     * current items and payments. Call after any item or payment change.
     */
    public function recalculate(): self
    {
        $subtotal = (float) $this->items()->sum('amount');

        $this->forceFill([
            'subtotal' => $subtotal,
            'total' => max(0, round($subtotal - (float) $this->discount, 2)),
            'paid_amount' => (float) $this->payments()->sum('amount'),
        ]);

        $this->status = match (true) {
            $this->status === 'void' => 'void',
            $this->total > 0 && (float) $this->paid_amount >= $this->total - 0.001 => 'paid',
            (float) $this->paid_amount > 0 => 'partial',
            default => 'unpaid',
        };

        $this->save();

        return $this;
    }
}
