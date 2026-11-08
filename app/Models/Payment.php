<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    /** Payment channels the clinic accepts. */
    public const METHODS = ['cash', 'gcash', 'bank_transfer', 'hmo', 'other'];

    protected $fillable = [
        'invoice_id',
        'amount',
        'method',
        'reference_no',
        'notes',
        'received_by',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function methodLabel(): string
    {
        return match ($this->method) {
            'gcash' => 'GCash',
            'bank_transfer' => 'Bank Transfer',
            'hmo' => 'HMO',
            'other' => 'Other',
            default => 'Cash',
        };
    }
}
