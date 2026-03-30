<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QueueRoomAssignment extends Model
{
    const ROOM_LABELS = [
        'laboratory'     => 'Laboratory',
        'xray_utz'       => 'X-Ray & Ultrasound',
        'drug_test'      => 'Drug Test',
        'interview_room' => 'Interview Room',
    ];

    protected $fillable = [
        'queue_ticket_id',
        'patient_visit_id',
        'room',
        'queue_number',
        'routing_sequence',
        'room_load_at_routing',
        'priority',
        'status',
        'call_count',
        'broadcast_at',
        'called_at',
        'served_at',
        'completed_at',
        'served_by',
    ];

    protected $casts = [
        'broadcast_at' => 'datetime',
        'called_at'    => 'datetime',
        'served_at'    => 'datetime',
        'completed_at' => 'datetime',
    ];

    // ── Relationships ──────────────────────────────

    public function ticket()
    {
        return $this->belongsTo(QueueTicket::class, 'queue_ticket_id');
    }

    // ── Scopes ─────────────────────────────────────

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeForRoom($query, string $room)
    {
        return $query->where('room', $room);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['waiting', 'calling', 'serving']);
    }

    // ── Accessors ──────────────────────────────────

    public function getRoomLabelAttribute(): string
    {
        return self::ROOM_LABELS[$this->room] ?? $this->room;
    }

    public function getPatientNameAttribute(): string
    {
        return $this->ticket?->patient?->full_name ?? '—';
    }

    public function getIssuedAtAttribute(): string
    {
        return $this->ticket?->issued_at
            ? $this->ticket->issued_at->format('h:i A')
            : '—';
    }

    public function getServicesAttribute(): array
    {
        return $this->ticket?->services_requested ?? [];
    }

    // ── Static helpers ─────────────────────────────

    public static function generateRoomNumber(string $room): string
    {
        $prefix = match($room) {
            'laboratory'     => 'L',
            'xray_utz'       => 'X',
            'drug_test'      => 'D',
            'interview_room' => 'I',
            default          => 'Q',
        };

        $count = static::whereDate('created_at', today())
                       ->where('room', $room)
                       ->count() + 1;

        return $prefix . str_pad($count, 3, '0', STR_PAD_LEFT);
    }
}
