<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class BookingPhoto extends Model
{
    protected $fillable = ['file_path', 'caption', 'is_active', 'sort_order'];

    protected $casts = ['is_active' => 'boolean'];

    public function getUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }

    public static function active()
    {
        return static::where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }
}
