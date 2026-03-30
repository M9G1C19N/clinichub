<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServicePriceHistory extends Model
{
    protected $table = 'service_price_history';

    protected $fillable = [
        'service_id',
        'old_price',
        'new_price',
        'reason',
        'changed_by',
        'changed_at',
    ];

    protected $casts = [
        'old_price'  => 'decimal:2',
        'new_price'  => 'decimal:2',
        'changed_at' => 'datetime',
    ];

    public function service()
    {
        return $this->belongsTo(ServiceCatalog::class, 'service_id');
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
