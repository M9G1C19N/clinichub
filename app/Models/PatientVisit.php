<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PatientVisit extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'visit_type',
        'employer_company',
        'referral_validated',
        'services_selected',
        'visit_date',
        'status',
        'result_claim_date',
        'chief_complaint',
        'created_by',
        'case_number',
    ];

    protected $casts = [
        'services_selected'  => 'array',
        'referral_validated' => 'boolean',
        'visit_date'         => 'datetime',
        'result_claim_date'  => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (PatientVisit $visit) {
              if (empty($visit->case_number) && !$visit->is_field_visit) {
            $year  = now()->format('y');
            $count = static::whereYear('created_at', now()->year)
                           ->whereNotNull('case_number')
                           ->withTrashed()
                           ->count() + 1;
            $visit->case_number = $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
        }
        });
    }

    // ── Relationships ──────────────────────────────────────

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Scopes ────────────────────────────────────────────

    public function scopeToday($query)
    {
        return $query->whereDate('visit_date', today());
    }

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
    public function invoice()
    {
        return $this->hasOne(Invoice::class, 'patient_visit_id');
    }
    public function vitals()
    {
        return $this->hasOne(PatientVital::class, 'patient_visit_id');
    }
    public function consultation()
    {
        return $this->hasOne(Consultation::class, 'patient_visit_id');
    }
    public function labRequest()
    {
        return $this->hasOne(LaboratoryRequest::class, 'patient_visit_id');
    }
    public function imagingRequests()
    {
        return $this->hasMany(ImagingRequest::class, 'patient_visit_id');
    }

    public function imagingRequest()
    {
        return $this->hasOne(ImagingRequest::class, 'patient_visit_id');
    }
    public function drugTestRequest()
    {
        return $this->hasOne(DrugTestRequest::class, 'patient_visit_id');
    }

}
