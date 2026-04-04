<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class DrugTestRequest extends Model
{
    use SoftDeletes, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'result', 'result_remarks', 'test_purpose', 'drugs_to_test', 'collector_name', 'head_of_lab_name'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $event) => "Drug Test {$this->code_number} {$event}");
    }

    protected $fillable = [
        'code_number','accession_number','patient_id','patient_visit_id',
        'company','test_purpose','drugs_to_test','specimen_type',
        'specimen_time','temp_in_range','specimen_volume',
        'specimen_appearance','specimen_sampling','specimen_collection',
        'collector_name','collector_license','collector_signature',
        'head_of_lab_name','head_of_lab_license','head_of_lab_signature',
        'collected_by','remarks',
        'result','result_remarks','certification_signed',
        'certification_date','status','released_at','released_by','specimen_date',
    ];

    protected $casts = [
        'drugs_to_test'       => 'array',
        'specimen_time'       => 'datetime',
        'released_at'         => 'datetime',
        'temp_in_range'       => 'boolean',
        'certification_signed'=> 'boolean',
        'certification_date'  => 'date',
        'specimen_date'       => 'date',
    ];

    // Auto-generate code number
    protected static function booted(): void
    {
        static::creating(function (DrugTestRequest $r) {
            $year  = now()->format('y'); // 2-digit year: 26
            $count = static::whereYear('created_at', now()->year)
                           ->withTrashed()->count() + 1;
            $r->code_number = 'R-' . str_pad($count, 4, '0', STR_PAD_LEFT);
            if (!$r->accession_number) {
                $r->accession_number = $r->code_number;
            }
        });
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function visit()
    {
        return $this->belongsTo(PatientVisit::class, 'patient_visit_id');
    }

    public function collectedBy()
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    public function getTestPurposeLabelAttribute(): string
    {
        return match($this->test_purpose) {
            'pre_employment'     => 'Pre-employment',
            'return_to_duty'     => 'Return to Duty',
            'random'             => 'Random',
            'reasonable_suspicion' => 'Reasonable Suspicion/Cause',
            'follow_up'          => 'Follow-up',
            'post_accident'      => 'Post-accident',
            'mandatory'          => 'Mandatory',
            default              => $this->test_purpose,
        };
    }

    public function getDrugsLabelAttribute(): string
    {
        $map = [
            'thc'             => 'THC',
            'met'             => 'MET',
            'thc_met'         => 'THC & MET',
            'thc_coc_pcp_opi_amp' => 'THC, COC, PCP, OPI, AMP',
        ];
        return collect($this->drugs_to_test)
            ->map(fn($d) => $map[$d] ?? strtoupper($d))
            ->join(', ');
    }

    public function getResultColorAttribute(): string
    {
        return match($this->result) {
            'negative'   => 'emerald',
            'positive'   => 'red',
            'cancelled'  => 'slate',
            default      => 'amber',
        };
    }
}
