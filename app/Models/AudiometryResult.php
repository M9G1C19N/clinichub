<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AudiometryResult extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'request_number', 'patient_id', 'patient_visit_id', 'requested_by',
        'audiometer_used', 'last_calibrated', 'examiner', 'reason_for_audiometry',
        'ch_hearing_loss', 'ch_hearing_loss_when', 'ch_hearing_aid',
        'ch_better_ear', 'ch_better_ear_which', 'ch_sudden_progression',
        'ch_ringing_noises', 'ch_drainage', 'ch_pain_discomfort', 'ch_medical_consultation',
        'otoscopic_right_ear', 'otoscopic_left_ear',
        're_250', 're_500', 're_1000', 're_1500', 're_2000', 're_3000', 're_4000', 're_8000', 're_average',
        'le_250', 'le_500', 'le_1000', 'le_1500', 'le_2000', 'le_3000', 'le_4000', 'le_8000', 'le_average',
        'remarks_right_ear', 'remarks_left_ear', 'recommendations',
        'examined_by_name', 'examined_by_license', 'examined_by_signature',
        'noted_by_name', 'noted_by_license', 'noted_by_signature',
        'status', 'result_date', 'result_time',
        'collected_at', 'released_at', 'released_by',
        'claim_status', 'claimed_at', 'claimed_by',
    ];

    protected $casts = [
        'ch_hearing_loss'        => 'boolean',
        'ch_hearing_aid'         => 'boolean',
        'ch_better_ear'          => 'boolean',
        'ch_sudden_progression'  => 'boolean',
        'ch_ringing_noises'      => 'boolean',
        'ch_drainage'            => 'boolean',
        'ch_pain_discomfort'     => 'boolean',
        'ch_medical_consultation'=> 'boolean',
        're_250'  => 'float', 're_500'  => 'float', 're_1000' => 'float', 're_1500' => 'float',
        're_2000' => 'float', 're_3000' => 'float', 're_4000' => 'float', 're_8000' => 'float',
        're_average' => 'float',
        'le_250'  => 'float', 'le_500'  => 'float', 'le_1000' => 'float', 'le_1500' => 'float',
        'le_2000' => 'float', 'le_3000' => 'float', 'le_4000' => 'float', 'le_8000' => 'float',
        'le_average' => 'float',
        'result_date'  => 'date',
        'collected_at' => 'datetime',
        'released_at'  => 'datetime',
        'claimed_at'   => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->request_number)) {
                $year = date('Y');
                $last = static::withTrashed()->whereYear('created_at', $year)->latest('id')->first();
                $seq  = $last ? (intval(substr($last->request_number, -5)) + 1) : 1;
                $model->request_number = 'AUD-' . $year . '-' . str_pad($seq, 5, '0', STR_PAD_LEFT);
            }
        });
    }

    public function patient()    { return $this->belongsTo(Patient::class); }
    public function visit()      { return $this->belongsTo(PatientVisit::class, 'patient_visit_id'); }
    public function requestedBy(){ return $this->belongsTo(User::class, 'requested_by'); }
    public function releasedBy() { return $this->belongsTo(User::class, 'released_by'); }
    public function claimedBy()  { return $this->belongsTo(User::class, 'claimed_by'); }
}
