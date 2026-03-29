<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QueueRoomAssignment extends Model
{
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

    // Room display labels
    const ROOM_LABELS = [
        'laboratory'     => 'Laboratory',
        'xray_utz'       => 'X-Ray & Ultrasound',
        'drug_test'      => 'Drug Test',
        'interview_room' => 'Interview Room',
    ];

    // Room queue number prefixes
    const ROOM_PREFIXES = [
        'laboratory'     => 'LAB',
        'xray_utz'       => 'XRAY',
        'drug_test'      => 'DRUG',
        'interview_room' => 'INT',
    ];

    // ── Relationships ──────────────────────────────────

    public function ticket()
    {
        return $this->belongsTo(QueueTicket::class, 'queue_ticket_id');
    }

    public function visit()
    {
        return $this->belongsTo(PatientVisit::class, 'patient_visit_id');
    }

    public function servedBy()
    {
        return $this->belongsTo(User::class, 'served_by');
    }

    // ── Helpers ────────────────────────────────────────

    public function getRoomLabelAttribute(): string
    {
        return self::ROOM_LABELS[$this->room] ?? $this->room;
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['waiting', 'directing', 'calling', 'serving']);
    }

    // Generate room-specific queue number: LAB-001
    public static function generateRoomNumber(string $room): string
    {
        $prefix = self::ROOM_PREFIXES[$room] ?? 'Q';

        $count = static::whereDate('created_at', today())
            ->where('room', $room)
            ->count() + 1;

        return $prefix . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
    }

    // ── Scopes ─────────────────────────────────────────

    public function scopeForRoom($query, string $room)
    {
        return $query->where('room', $room);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['waiting', 'directing', 'calling', 'serving']);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }
}
