<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, SoftDeletes, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'employee_id', 'specialization', 'prc_number', 'ptr_number', 'department', 'is_active', 'must_change_password', 's2_number'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $event) => "User {$this->name} {$event}");
    }

    protected $fillable = [
        'name',
        'email',
        'password',
        'employee_id',
        'specialization',       // For doctors: e.g., "General Practitioner"
        'prc_number',           // Doctors/nurses PRC license
        'ptr_number',           // Doctors Professional Tax Receipt
        'department',           // lab, xray_utz, drug_test, reception, nursing
        'photo_path',
        'is_active',
        'must_change_password', // Force change on first login
        's2_number',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'is_active'         => 'boolean',
        'must_change_password' => 'boolean',
    ];

    // Relationships
    public function esignature()
    {
        return $this->hasOne(Esignature::class);
    }

    public function createdPatients()
    {
        return $this->hasMany(Patient::class, 'created_by');
    }
}
