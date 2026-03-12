<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TenantDocument extends Model
{
    use HasFactory;

    public const TYPE_OFFICIAL_ID = 'official_id';
    public const TYPE_PROOF_OF_INCOME = 'proof_of_income';
    public const TYPE_PROOF_OF_ADDRESS = 'proof_of_address';
    public const TYPE_EMPLOYMENT_LETTER = 'employment_letter';
    public const TYPE_BANK_STATEMENTS = 'bank_statements';
    public const TYPE_REFERENCES = 'references';
    public const TYPE_SIGNED_APPLICATION = 'signed_application';

    public const REQUIRED_DOCUMENTS = [
        self::TYPE_OFFICIAL_ID => 'Identificacion oficial',
        self::TYPE_PROOF_OF_INCOME => 'Comprobante de ingresos',
        self::TYPE_PROOF_OF_ADDRESS => 'Comprobante de domicilio',
        self::TYPE_EMPLOYMENT_LETTER => 'Carta laboral',
        self::TYPE_BANK_STATEMENTS => 'Estados de cuenta',
        self::TYPE_REFERENCES => 'Referencias',
        self::TYPE_SIGNED_APPLICATION => 'Solicitud firmada',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_UPLOADED = 'uploaded';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_EXPIRED = 'expired';

    public const STATUS_LABELS = [
        self::STATUS_PENDING => 'Pendiente',
        self::STATUS_UPLOADED => 'En revision',
        self::STATUS_APPROVED => 'Vigente',
        self::STATUS_REJECTED => 'Rechazado',
        self::STATUS_EXPIRED => 'Vencido',
    ];

    public const STATUS_BADGE_CLASSES = [
        self::STATUS_PENDING => 'badge-light-secondary text-secondary',
        self::STATUS_UPLOADED => 'badge-light-primary text-primary',
        self::STATUS_APPROVED => 'badge-light-success text-success',
        self::STATUS_REJECTED => 'badge-light-danger text-danger',
        self::STATUS_EXPIRED => 'badge-light-warning text-warning',
    ];

    protected $fillable = [
        'tenant_id',
        'document_type',
        'label',
        'file_path',
        'status',
        'uploaded_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
            'expires_at' => 'date',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(TenantDocumentVersion::class);
    }

    public function latestVersion(): HasOne
    {
        return $this->hasOne(TenantDocumentVersion::class)->latestOfMany('version_number');
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
