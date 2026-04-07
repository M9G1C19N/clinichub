<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackageDiscount extends Model
{
    protected $fillable = [
        'package_key',
        'package_name',
        'service_codes',
        'package_price',
        'addon_drugtest_price',
        'is_active',
        'updated_by',
    ];

    protected $casts = [
        'service_codes'        => 'array',
        'package_price'        => 'decimal:2',
        'addon_drugtest_price' => 'decimal:2',
        'is_active'            => 'boolean',
    ];

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
