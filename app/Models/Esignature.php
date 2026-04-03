<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Esignature extends Model
{
    protected $fillable = [
        'user_id', 'title', 'license_number', 'ptr_number',
        'signature_path', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getSignatureUrlAttribute(): ?string
    {
        return $this->signature_path
            ? asset('storage/' . $this->signature_path)
            : null;
    }
}
