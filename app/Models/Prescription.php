<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Prescription extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'rx_number', 'patient_id', 'patient_visit_id', 'doctor_id',
        'items', 'doctor_name', 'doctor_prc', 'doctor_ptr',
        'doctor_s2', 'doctor_specialization',
        'patient_name', 'patient_age_sex', 'patient_address',
        'notes', 'rx_date', 'is_controlled',
    ];

    protected $casts = [
        'items'         => 'array',
        'rx_date'       => 'date',
        'is_controlled' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Prescription $rx) {
            $year  = now()->year;
            $count = static::whereYear('created_at', $year)
                           ->withTrashed()->count() + 1;
            $rx->rx_number = 'RX-' . $year . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
        });
    }

    public function patient()  { return $this->belongsTo(Patient::class); }
    public function visit()    { return $this->belongsTo(PatientVisit::class, 'patient_visit_id'); }
    public function doctor()   { return $this->belongsTo(User::class, 'doctor_id'); }
}
