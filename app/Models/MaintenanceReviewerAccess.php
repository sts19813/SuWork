<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class MaintenanceReviewerAccess extends Model
{
    use HasFactory;

    public const RANGE_RELATIVE = 'relative';

    public const RANGE_CUSTOM = 'custom';

    protected $fillable = [
        'user_id',
        'range_type',
        'days_before',
        'days_after',
        'starts_on',
        'ends_on',
    ];

    protected function casts(): array
    {
        return [
            'days_before' => 'integer',
            'days_after' => 'integer',
            'starts_on' => 'date',
            'ends_on' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return array{0: Carbon, 1: Carbon}|null */
    public function ticketDateRange(?Carbon $today = null): ?array
    {
        $today ??= now()->startOfDay();

        if ($this->range_type === self::RANGE_CUSTOM) {
            if (! $this->starts_on || ! $this->ends_on) {
                return null;
            }

            return [$this->starts_on->copy()->startOfDay(), $this->ends_on->copy()->endOfDay()];
        }

        return [
            $today->copy()->subDays((int) ($this->days_before ?? 0))->startOfDay(),
            $today->copy()->addDays((int) ($this->days_after ?? 0))->endOfDay(),
        ];
    }
}
