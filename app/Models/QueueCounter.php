<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QueueCounter extends Model
{
    protected $fillable = [
        'counter_name',
        'counter_code',
        'assigned_types',
        'current_user_id',
        'is_active',
    ];

    protected $casts = [
        'assigned_types' => 'array',
        'is_active'      => 'boolean',
    ];

    public function currentUser()
    {
        return $this->belongsTo(User::class, 'current_user_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
