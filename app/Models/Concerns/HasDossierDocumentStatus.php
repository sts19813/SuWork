<?php

namespace App\Models\Concerns;

trait HasDossierDocumentStatus
{
    public function getComputedStatusAttribute(): string
    {
        if (blank($this->file_path)) {
            return self::STATUS_PENDING;
        }

        if ($this->expires_at?->lt(today())) {
            return self::STATUS_EXPIRED;
        }

        return self::STATUS_APPROVED;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->computed_status] ?? 'Sin archivo';
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return self::STATUS_BADGE_CLASSES[$this->computed_status] ?? 'badge-light-secondary text-secondary';
    }
}
