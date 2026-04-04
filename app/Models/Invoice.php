<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Invoice extends Model
{
    use SoftDeletes, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'total_amount', 'discount_amount', 'paid_amount', 'balance', 'notes'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $event) => "Invoice {$this->invoice_number} {$event}");
    }

    protected $fillable = [
        'invoice_number', 'patient_id', 'patient_visit_id',
        'status', 'total_amount', 'discount_amount',
        'paid_amount', 'balance', 'notes', 'created_by', 'paid_at',
    ];

    protected $casts = [
        'total_amount'    => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'paid_amount'     => 'decimal:2',
        'balance'         => 'decimal:2',
        'paid_at'         => 'datetime',
    ];

    // ── Auto-generate invoice number ───────────────────

    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice) {
            $year  = now()->year;
            $count = static::whereYear('created_at', $year)
                           ->withTrashed()->count() + 1;
            $invoice->invoice_number = 'INV-' . $year . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
        });
    }

    // ── Relationships ──────────────────────────────────

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function visit()
    {
        return $this->belongsTo(PatientVisit::class, 'patient_visit_id');
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Helpers ────────────────────────────────────────

    public function recalculate(): void
    {
        $total      = $this->items()->sum('subtotal');
        $paid       = $this->payments()->sum('amount');
        $balance    = $total - $this->discount_amount - $paid;

        $status = match(true) {
            $balance <= 0      => 'paid',
            $paid > 0          => 'partial',
            default            => 'unpaid',
        };

        $this->update([
            'total_amount' => $total,
            'paid_amount'  => $paid,
            'balance'      => max(0, $balance),
            'status'       => $status,
            'paid_at'      => $status === 'paid' ? now() : null,
        ]);
    }
}
