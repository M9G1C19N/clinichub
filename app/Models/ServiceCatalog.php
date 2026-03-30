<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ServiceCatalog extends Model
{
    protected $table = 'service_catalog';

    protected $fillable = [
        'service_code',
        'service_name',
        'category',
        'room',
        'base_price',
        'description',
        'requires_fasting',
        'turnaround_hours',
        'is_active',
        'price_changed_by',
        'price_changed_at',
    ];

    protected $casts = [
        'base_price'       => 'decimal:2',
        'requires_fasting' => 'boolean',
        'is_active'        => 'boolean',
        'price_changed_at' => 'datetime',
    ];

    // ── Auto-log price changes ─────────────────────────

    protected static function booted(): void
    {
        static::updating(function (ServiceCatalog $service) {
            if ($service->isDirty('base_price')) {
                ServicePriceHistory::create([
                    'service_id' => $service->id,
                    'old_price'  => $service->getOriginal('base_price'),
                    'new_price'  => $service->base_price,
                    'changed_by' => Auth::id(),
                    'changed_at' => now(),
                ]);

                $service->price_changed_by = Auth::id();
                $service->price_changed_at = now();
            }
        });
    }

    // ── Relationships ──────────────────────────────────

    public function priceHistory()
    {
        return $this->hasMany(ServicePriceHistory::class, 'service_id')
                    ->orderByDesc('changed_at');
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'price_changed_by');
    }

    // ── Scopes ─────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('service_name', 'like', "%{$search}%")
              ->orWhere('service_code', 'like', "%{$search}%");
        });
    }

    // ── Formatted price ────────────────────────────────

    public function getFormattedPriceAttribute(): string
    {
        return '₱ ' . number_format($this->base_price, 2);
    }
}
