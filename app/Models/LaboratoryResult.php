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
            if ($result->result_value === null || $result->result_value === '') return;
            if (!is_numeric($result->result_value)) return;

            $val   = floatval($result->result_value);
            $range = $test->normal_range_general
                ?? $test->normal_range_male
                ?? $test->normal_range_female;

            if (!$range || $range === '—') return;

            if (str_starts_with($range, '<')) {
                $max = floatval(ltrim($range, '<'));
                $result->is_abnormal   = $val >= $max;
                $result->abnormal_flag = $val >= $max ? 'H' : null;
            } elseif (str_starts_with($range, '>')) {
                $min = floatval(ltrim($range, '>'));
                $result->is_abnormal   = $val <= $min;
                $result->abnormal_flag = $val <= $min ? 'L' : null;
            } else {
                $parts = explode('-', $range);
                if (count($parts) === 2 && is_numeric($parts[0]) && is_numeric($parts[1])) {
                    $min = floatval($parts[0]);
                    $max = floatval($parts[1]);
                    if ($val < $min)      { $result->is_abnormal = true;  $result->abnormal_flag = 'L'; }
                    elseif ($val > $max)  { $result->is_abnormal = true;  $result->abnormal_flag = 'H'; }
                    else                  { $result->is_abnormal = false; $result->abnormal_flag = null; }
                }
            }
        });
    }
}
