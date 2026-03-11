<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyInventoryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_inventory_area_id',
        'name',
        'condition',
        'notes',
    ];

    public function area(): BelongsTo
    {
        return $this->belongsTo(PropertyInventoryArea::class, 'property_inventory_area_id');
    }
}

