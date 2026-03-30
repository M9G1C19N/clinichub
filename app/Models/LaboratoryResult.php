<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LaboratoryResult extends Model
{
    protected $fillable = [
        'lab_request_id', 'lab_test_id', 'result_value',
        'unit', 'normal_range_display', 'is_abnormal',
        'abnormal_flag', 'remarks', 'performed_by',
    ];

    protected $casts = [
        'is_abnormal' => 'boolean',
    ];

    public function labTest()
    {
        return $this->belongsTo(LabTest::class, 'lab_test_id');
    }

    public function request()
    {
        return $this->belongsTo(LaboratoryRequest::class, 'lab_request_id');
    }

    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    // Auto-flag abnormal when saving numeric results
    protected static function booted(): void
    {
        static::saving(function (LaboratoryResult $result) {
            $test = $result->labTest;
            if (!$test || $test->is_text_result) return;

            $val = floatval($result->result_value);
            if ($result->result_value === null || $result->result_value === '') return;

            if ($test->normal_min && $test->normal_max) {
                if ($val < $test->normal_min) {
                    $result->is_abnormal   = true;
                    $result->abnormal_flag = 'L';
                } elseif ($val > $test->normal_max) {
                    $result->is_abnormal   = true;
                    $result->abnormal_flag = 'H';
                } else {
                    $result->is_abnormal   = false;
                    $result->abnormal_flag = null;
                }
            }
        });
    }
}
