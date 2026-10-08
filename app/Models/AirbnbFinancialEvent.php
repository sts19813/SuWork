<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AirbnbFinancialEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'external_event_id',
        'event_type',
        'external_reservation_id',
        'amount',
        'currency',
        'occurred_on',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'occurred_on' => 'date',
            'payload' => 'array',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
