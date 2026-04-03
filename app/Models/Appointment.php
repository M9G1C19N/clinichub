<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Appointment extends Model
{
    protected $fillable = [
        'appointment_number',
        'patient_name',
        'patient_email',
        'patient_phone',
        'patient_dob',
        'patient_gender',
        'service_type',
        'chief_complaint',
        'preferred_date',
        'preferred_time',
        'status',
        'confirmed_by',
        'confirmed_at',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
        'admin_notes',
        'visit_id',
        'patient_id',
    ];

    protected $casts = [
        'preferred_date' => 'date',
        'patient_dob'    => 'date',
        'confirmed_at'   => 'datetime',
        'cancelled_at'   => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────────────

    public function confirmedBy()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function visit()
    {
        return $this->belongsTo(PatientVisit::class, 'visit_id');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    public static function generateNumber(): string
    {
        $prefix = 'APT-' . now()->format('Ymd');
        $last   = static::where('appointment_number', 'like', $prefix . '-%')
                        ->orderByDesc('appointment_number')
                        ->value('appointment_number');

        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;
        return $prefix . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    public static function serviceLabel(string $type): string
    {
        return match ($type) {
            'general_consultation' => 'General Consultation',
            'laboratory'           => 'Laboratory',
            'xray_utz'             => 'X-Ray / Ultrasound',
            'drug_test'            => 'Drug Test',
            'physical_exam'        => 'Physical Exam (PE)',
            'prenatal'             => 'Prenatal Check-up',
            'other'                => 'Other / Inquiry',
            default                => ucfirst(str_replace('_', ' ', $type)),
        };
    }

    public function getServiceLabelAttribute(): string
    {
        return static::serviceLabel($this->service_type);
    }

    public function getPreferredTimeLabelAttribute(): string
    {
        return match ($this->preferred_time) {
            'morning'   => 'Morning (8:00 AM – 12:00 PM)',
            'afternoon' => 'Afternoon (12:00 PM – 5:00 PM)',
            null        => 'Any time',
            default     => $this->preferred_time,
        };
    }
}
