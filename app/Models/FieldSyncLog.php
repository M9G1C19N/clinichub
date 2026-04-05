<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FieldSyncLog extends Model
{
    protected $fillable = [
        'original_filename',
        'device_id',
        'visits_imported',
        'patients_created',
        'patients_matched',
        'visits_skipped',
        'skipped_details',
        'imported_by',
    ];

    protected $casts = [
        'skipped_details' => 'array',
    ];

    public function importedBy()
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    public function visits()
    {
        return $this->hasMany(PatientVisit::class, 'field_sync_log_id');
    }
}
