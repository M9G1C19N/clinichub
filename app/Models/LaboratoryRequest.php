<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\LaboratoryResult;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class LaboratoryRequest extends Model
{
    use SoftDeletes, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'priority', 'clinical_notes', 'released_at', 'examined_by_name', 'noted_by_name'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $event) => "Lab Request {$this->request_number} {$event}");
    }

    protected $fillable = [
        'request_number', 'patient_id', 'patient_visit_id',
        'requested_by', 'request_date', 'priority', 'status',
        'collected_at',
        'clinical_notes', 'released_at', 'released_by',
        'examined_by_name', 'examined_by_license', 'examined_by_signature',
        'noted_by_name', 'noted_by_license', 'noted_by_signature',
        'result_date', 'result_time',
    ];

    protected $casts = [
        'request_date' => 'date',
        'collected_at' => 'datetime',
        'released_at'  => 'datetime',
        'result_date'  => 'date',
    ];

    // ── Auto-generate request number ───────────────────
    protected static function booted(): void
    {
        static::creating(function (LaboratoryRequest $req) {
            $year  = now()->year;
            $count = static::whereYear('created_at', $year)
                           ->withTrashed()->count() + 1;
            $req->request_number = 'LAB-' . $year . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
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

    public function results()
    {
        return $this->hasMany(LaboratoryResult::class, 'lab_request_id')
                    ->orderBy('lab_test_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function releasedBy()
    {
        return $this->belongsTo(User::class, 'released_by');
    }

    public function getHasAbnormalAttribute(): bool
    {
        return $this->results()->where('is_abnormal', true)->exists();
    }

    public function getAbnormalCountAttribute(): int
    {
        return $this->results()->where('is_abnormal', true)->count();
    }
}
