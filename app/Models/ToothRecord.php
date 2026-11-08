<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per tooth that needs attention or already has dental work.
 * A record with status "healthy" is deleted — its absence means healthy.
 */
class ToothRecord extends Model
{
    /** FDI numbering — upper jaw (quadrants 1 and 2). */
    public const UPPER_RIGHT = [18, 17, 16, 15, 14, 13, 12, 11];

    public const UPPER_LEFT = [21, 22, 23, 24, 25, 26, 27, 28];

    /** FDI numbering — lower jaw (quadrants 3 and 4). */
    public const LOWER_LEFT = [31, 32, 33, 34, 35, 36, 37, 38];

    public const LOWER_RIGHT = [48, 47, 46, 45, 44, 43, 42, 41];

    /** Statuses stored on the chart (healthy teeth have no row). */
    public const STATUSES = ['caries', 'filled', 'crown', 'root_canal', 'missing', 'implant', 'bridge', 'denture'];

    /** Legend metadata: label + Tailwind classes used by the odontogram UI. */
    public const STATUS_META = [
        'caries' => ['label' => 'Caries (Decay)', 'color' => 'bg-rose-500 border-rose-600 text-white'],
        'filled' => ['label' => 'Filled', 'color' => 'bg-sky-500 border-sky-600 text-white'],
        'crown' => ['label' => 'Crown', 'color' => 'bg-amber-500 border-amber-600 text-white'],
        'root_canal' => ['label' => 'Root Canal', 'color' => 'bg-indigo-500 border-indigo-600 text-white'],
        'missing' => ['label' => 'Missing', 'color' => 'bg-slate-700 border-slate-800 text-white'],
        'implant' => ['label' => 'Implant', 'color' => 'bg-violet-500 border-violet-600 text-white'],
        'bridge' => ['label' => 'Bridge', 'color' => 'bg-teal-500 border-teal-600 text-white'],
        'denture' => ['label' => 'Denture', 'color' => 'bg-fuchsia-500 border-fuchsia-600 text-white'],
    ];

    protected $fillable = [
        'patient_id',
        'tooth_number',
        'status',
        'note',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * All 32 FDI tooth numbers, upper then lower jaw.
     *
     * @return list<int>
     */
    public static function teeth(): array
    {
        return array_values(array_merge(
            self::UPPER_RIGHT,
            self::UPPER_LEFT,
            self::LOWER_LEFT,
            self::LOWER_RIGHT,
        ));
    }

    /**
     * Chart layout: jaw row => tooth order as rendered left→right
     * (patient's right first, the way a printed dental chart reads).
     *
     * @return array<string, list<int>>
     */
    public static function chartLayout(): array
    {
        return [
            'upper' => array_merge(self::UPPER_RIGHT, self::UPPER_LEFT),
            'lower' => array_merge(self::LOWER_RIGHT, self::LOWER_LEFT),
        ];
    }

    public function statusLabel(): string
    {
        return self::STATUS_META[$this->status]['label'] ?? $this->status;
    }
}
