<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientVital extends Model
{
    protected $fillable = [
        'patient_id',
        'patient_visit_id',
        'weight_kg',
        'height_cm',
        'bmi',
        'blood_pressure_systolic',
        'blood_pressure_diastolic',
        'pulse_rate',
        'respiratory_rate',
        'temperature_celsius',
        'oxygen_saturation',
        'visual_acuity_right',
        'visual_acuity_left',
        'ishihara_result',
        'nurse_notes',
        'recorded_by',
        // Medical History
        'present_symptoms', 'past_illnesses_flags', 'past_illnesses_remarks',
        'family_history', 'accidents_injuries', 'surgical_history_detail',
        'allergies_flags', 'allergies_others',
        'menstrual_cycle', 'lmp', 'ob_gravida', 'ob_para', 'ob_nulligravida',
        'tobacco_use', 'tobacco_use_details',
        'alcohol_use', 'alcohol_use_details',
        'pe_remarks',
        // Additional vitals
        'conversational_hearing',
        'visual_acuity_right_corrected', 'visual_acuity_left_corrected',
        'visual_acuity_near_right', 'visual_acuity_near_left',
        'visual_acuity_near_right_corrected', 'visual_acuity_near_left_corrected',
        'color_vision_result',
        'pe_findings_normal', 'pe_findings_details', 'pe_findings_remarks',
    ];

    protected $casts = [
        'weight_kg'               => 'decimal:2',
        'height_cm'               => 'decimal:2',
        'bmi'                     => 'decimal:2',
        'temperature_celsius'     => 'decimal:1',
        'past_illnesses_flags' => 'array',
        'allergies_flags'      => 'array',
        'pe_findings_normal'   => 'array',
        'pe_findings_details'  => 'array',
        'ob_nulligravida'      => 'boolean',
    ];

    // ── Auto-calculate BMI ─────────────────────────

    protected static function booted(): void
    {
        static::saving(function (PatientVital $vital) {
            if ($vital->weight_kg && $vital->height_cm) {
                $heightM     = $vital->height_cm / 100;
                $vital->bmi  = round($vital->weight_kg / ($heightM * $heightM), 2);
            }
        });
    }

    // ── Relationships ──────────────────────────────

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function visit()
    {
        return $this->belongsTo(PatientVisit::class, 'patient_visit_id');
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    // ── Accessors ──────────────────────────────────

    public function getBpAttribute(): string
    {
        if ($this->blood_pressure_systolic && $this->blood_pressure_diastolic) {
            return "{$this->blood_pressure_systolic}/{$this->blood_pressure_diastolic} mmHg";
        }
        return '—';
    }

    public function getBmiCategoryAttribute(): string
    {
        if (!$this->bmi) return '—';

        return match(true) {
            $this->bmi < 18.5 => 'Underweight',
            $this->bmi < 25.0 => 'Normal',
            $this->bmi < 30.0 => 'Overweight',
            default           => 'Obese',
        };
    }

    public function getBmiColorAttribute(): string
    {
        if (!$this->bmi) return 'text-slate-400';

        return match(true) {
            $this->bmi < 18.5 => 'text-blue-600',
            $this->bmi < 25.0 => 'text-emerald-600',
            $this->bmi < 30.0 => 'text-amber-600',
            default           => 'text-red-600',
        };
    }
}
