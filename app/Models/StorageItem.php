<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StorageItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_type',
        'name',
        'description',
        'brand',
        'condition',
        'quantity',
        'photo',
    ];

    public function logs()
    {
        return $this->hasMany(StorageItemLog::class);
    }
}
