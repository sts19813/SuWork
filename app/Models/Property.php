<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Property extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_AVAILABLE = 'available';
    public const STATUS_IN_PROCESS = 'in_process';
    public const STATUS_BLOCKED = 'blocked';
    public const STATUS_RENTED = 'rented';

    public const STATUS_LABELS = [
        self::STATUS_DRAFT => 'Borrador',
        self::STATUS_AVAILABLE => 'Disponible',
        self::STATUS_IN_PROCESS => 'En proceso',
        self::STATUS_BLOCKED => 'Bloqueada',
        self::STATUS_RENTED => 'Rentada',
    ];

    public const STATUS_BADGE_CLASSES = [
        self::STATUS_DRAFT => 'badge-light-secondary text-secondary',
        self::STATUS_AVAILABLE => 'badge-light-warning text-warning',
        self::STATUS_IN_PROCESS => 'badge-light-info text-info',
        self::STATUS_BLOCKED => 'badge-light-danger text-danger',
        self::STATUS_RENTED => 'badge-light-success text-success',
    ];

    protected $fillable = [
        'internal_name',
        'internal_reference',
        'property_type_id',
        'zone_id',
        'full_address',
        'complex_name',
        'official_number',
        'unit_number',
        'facade_photo_path',
        'status',
        'current_tenant_name',
        'contract_expires_at',
        'onboarding_step',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'contract_expires_at' => 'date',
            'onboarding_step' => 'integer',
        ];
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(PropertyType::class, 'property_type_id');
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function owners(): HasMany
    {
        return $this->hasMany(PropertyOwner::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(PropertyDocument::class);
    }

    public function pendingDocuments(): HasMany
    {
        return $this->documents()->where('status', PropertyDocument::STATUS_PENDING);
    }

    public function inventoryAreas(): HasMany
    {
        return $this->hasMany(PropertyInventoryArea::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst(str_replace('_', ' ', $this->status));
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return self::STATUS_BADGE_CLASSES[$this->status] ?? 'badge-light-secondary text-secondary';
    }
}

