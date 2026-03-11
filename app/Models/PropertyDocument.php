<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyDocument extends Model
{
    use HasFactory;

    public const TYPE_TITLE_DEED = 'title_deed';
    public const TYPE_PROPERTY_TAX = 'property_tax';
    public const TYPE_CFE_RECEIPT = 'cfe_receipt';
    public const TYPE_WATER_RECEIPT = 'water_receipt';
    public const TYPE_CADASTRAL_ID = 'cadastral_id';

    public const REQUIRED_DOCUMENTS = [
        self::TYPE_TITLE_DEED => 'Escritura o constancia registral',
        self::TYPE_PROPERTY_TAX => 'Predial',
        self::TYPE_CFE_RECEIPT => 'Recibo CFE',
        self::TYPE_WATER_RECEIPT => 'Recibo de agua',
        self::TYPE_CADASTRAL_ID => 'Cédula catastral',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_UPLOADED = 'uploaded';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const STATUS_LABELS = [
        self::STATUS_PENDING => 'Pendiente',
        self::STATUS_UPLOADED => 'Cargado',
        self::STATUS_APPROVED => 'Aprobado',
        self::STATUS_REJECTED => 'Rechazado',
    ];

    public const STATUS_BADGE_CLASSES = [
        self::STATUS_PENDING => 'badge-light-secondary text-secondary',
        self::STATUS_UPLOADED => 'badge-light-info text-info',
        self::STATUS_APPROVED => 'badge-light-success text-success',
        self::STATUS_REJECTED => 'badge-light-danger text-danger',
    ];

    protected $fillable = [
        'property_id',
        'document_type',
        'label',
        'file_path',
        'status',
        'uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst($this->status);
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return self::STATUS_BADGE_CLASSES[$this->status] ?? 'badge-light-secondary text-secondary';
    }
}

