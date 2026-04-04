<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Payment extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['amount', 'method', 'reference_number', 'notes'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $event) => "Payment {$event}");
    }
    protected $fillable = [
        'invoice_id', 'amount', 'method',
        'reference_number', 'notes', 'received_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
