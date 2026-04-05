<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ImagingRequest extends Model
{
    use SoftDeletes, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'imaging_type', 'radiographic_findings', 'impression', 'is_provisional', 'radiologist_name', 'rad_tech_name'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $event) => "X-Ray/Imaging {$this->request_number} {$event}");
    }

    protected $fillable = [
        'request_number', 'patient_id', 'patient_visit_id',
        'imaging_type', 'requested_by',
        'radiographic_findings', 'impression', 'is_provisional',
        'status', 'collected_at',
        'rad_tech_name', 'rad_tech_license', 'rad_tech_signature',
        'radiologist_name', 'radiologist_license', 'radiologist_signature',
        'released_at', 'released_by',
        'exam_date', 'exam_time',
    ];

    protected $casts = [
        'is_provisional' => 'boolean',
        'collected_at'   => 'datetime',
        'released_at'    => 'datetime',
        'exam_date'      => 'date',
    ];

    // ── Auto-generate request number ───────────────────
    protected static function booted(): void
    {
        static::creating(function (ImagingRequest $req) {
            $year  = now()->year;
            $count = static::whereYear('created_at', $year)
                           ->withTrashed()->count() + 1;
            $req->request_number = 'IMG-' . $year . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
        });
    }

    // ── Relationships ──────────────────────────────────
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function visit()
    {
        return $this->belongsTo(PatientVisit::class, 'patient_visit_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    // ── Accessors ──────────────────────────────────────
    public function getImagingTypeLabelAttribute(): string
    {
        return match($this->imaging_type) {
            'chest_xray_pa'      => 'Chest X-Ray PA View',
            'kub'                => 'KUB (Kidney, Ureter, Bladder)',
            'ultrasound_abdomen' => 'Ultrasound - Whole Abdomen',
            'ultrasound_ob'      => 'Ultrasound - OB',
            'ultrasound_pelvis'  => 'Ultrasound - Pelvis',
            'ecg'                => 'Electrocardiogram (ECG)',
            'other'              => 'Other',
            default              => $this->imaging_type,
        };
    }
}
