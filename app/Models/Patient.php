<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Patient extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'patient_code',
        'first_name',
        'last_name',
        'middle_name',
        'date_of_birth',
        'sex',
        'civil_status',
        'contact_number',
        'email',
        'address',
        'philhealth_number',
        'blood_type',
        'occupation',
        'emergency_contact_name',
        'emergency_contact_number',
        'photo_path',
        'visit_type_default',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'is_active'     => 'boolean',
    ];

    // ── Accessors ──────────────────────────────────────────

    /**
     * Full name: Last, First Middle
     */
    public function getFullNameAttribute(): string
    {
        $middle = $this->middle_name
            ? ' ' . substr($this->middle_name, 0, 1) . '.'
            : '';

        return "{$this->last_name}, {$this->first_name}{$middle}";
    }

    /**
     * Age computed from date_of_birth
     */
    public function getAgeAttribute(): int
    {
        return Carbon::parse($this->date_of_birth)->age;
    }

    /**
     * Age/Sex short string e.g. "34/M"
     */
    public function getAgeSexAttribute(): string
    {
        return $this->age . '/' . strtoupper(substr($this->sex, 0, 1));
    }

    // ── Auto-generate patient_code ─────────────────────────

    protected static function booted(): void
    {
        static::creating(function (Patient $patient) {
            $year  = now()->year;
            $count = static::whereYear('created_at', $year)->withTrashed()->count() + 1;
            $patient->patient_code = 'PT-' . $year . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
        });
    }

    // ── Relationships ──────────────────────────────────────

    public function registeredBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function visits()
    {
        return $this->hasMany(PatientVisit::class);
    }

    public function latestVisit()
    {
        return $this->hasOne(PatientVisit::class)->latestOfMany();
    }

    // ── Scopes ────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
              ->orWhere('last_name', 'like', "%{$search}%")
              ->orWhere('patient_code', 'like', "%{$search}%")
              ->orWhere('contact_number', 'like', "%{$search}%")
              ->orWhere('philhealth_number', 'like', "%{$search}%");
        });
    }
}
