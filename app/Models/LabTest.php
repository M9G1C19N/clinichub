<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabTest extends Model
{
    protected $fillable = [
        'test_name', 'test_code', 'category', 'unit',
        'normal_range_male', 'normal_range_female', 'normal_range_general',
        'normal_min', 'normal_max', 'is_text_result', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'is_text_result' => 'boolean',
        'is_active'      => 'boolean',
        'normal_min'     => 'decimal:3',
        'normal_max'     => 'decimal:3',
    ];

    public function getNormalRangeForPatient(string $sex): string
    {
        if ($sex === 'male'   && $this->normal_range_male)   return $this->normal_range_male;
        if ($sex === 'female' && $this->normal_range_female) return $this->normal_range_female;
        return $this->normal_range_general ?? '—';
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCategory($query, string $cat)
    {
        return $query->where('category', $cat);
    }
}
