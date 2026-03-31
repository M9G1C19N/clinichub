<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Consultation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'patient_id', 'patient_visit_id', 'visit_type',
        'chief_complaint',
        'soap_subjective', 'soap_objective', 'soap_assessment', 'soap_plan',
        'icd10_code', 'icd10_description', 'diagnosis_type',
        // PE header
        'pe_classification', 'pe_findings', 'pe_recommendation',
        'employer_company', 'position_applied', 'requesting_physician',
        // Physical Exam
        'pe_heent', 'pe_chest_lungs', 'pe_heart', 'pe_abdomen',
        'pe_extremities', 'pe_neurological', 'pe_genitourinary',
        'pe_skin', 'pe_others',
        // Medical History
        'past_illnesses', 'surgical_history', 'allergies',
        'current_medications', 'family_history',
        // Common
        'doctor_notes', 'is_finalized', 'finalized_at',
        'doctor_id', 'consultation_date', 'follow_up_date',
        'essentially_normal', 'status',
    ];

    protected $casts = [
        'is_finalized'  => 'boolean',
        'finalized_at'  => 'datetime',
    ];

    // ── Relationships ──────────────────────────────

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function visit()
    {
        return $this->belongsTo(PatientVisit::class, 'patient_visit_id');
    }

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    // ── Accessors ──────────────────────────────────

    public function getPeClassificationLabelAttribute(): string
    {
        return match($this->pe_classification) {
            'A' => 'Class A — Physically fit for all types of work. No defects noted.',
            'B' => 'Class B — Physically fit for all types of work. Has minor and curable ailment that offers no handicap to the job applied.',
            'C' => 'Class C — With findings that are generally unacceptable. Employment at risk and at the discretion of management.',
            'D' => 'Class D — Not fit for employment.',
            'E' => 'Class E — Lacking in requirements or need further evaluation.',
            default => '—',
        };
    }

    public function getPeClassificationColorAttribute(): string
    {
        return match($this->pe_classification) {
            'A'     => 'emerald',
            'B'     => 'blue',
            'C'     => 'amber',
            'D'     => 'orange',
            'E'     => 'red',
            default => 'slate',
        };
    }
}
