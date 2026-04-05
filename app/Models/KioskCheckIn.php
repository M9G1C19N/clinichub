<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KioskCheckIn extends Model
{
    protected $table = 'kiosk_checkins';

    protected $fillable = [
        'patient_id',
        'visit_type',
        'priority',
        'services_requested',
        'employer_company',
        'chief_complaint',
        'status',
        'processed_at',
    ];

    protected $casts = [
        'services_requested' => 'array',
        'processed_at'       => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
