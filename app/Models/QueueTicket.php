<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\QueueRoomAssignment;
class QueueTicket extends Model
{
    protected $fillable = [
        'ticket_number',
        'patient_id',
        'patient_visit_id',
        'queue_counter_id',
        'visit_type',
        'priority',
        'status',
        'services_requested',
        'issued_at',
        'called_at',
        'completed_at',
        'issued_by',
    ];

    protected $casts = [
        'services_requested' => 'array',
        'issued_at'          => 'datetime',
        'called_at'          => 'datetime',
        'completed_at'       => 'datetime',
    ];

    // ── Relationships ──────────────────────────────────
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function visit()
    {
        return $this->belongsTo(PatientVisit::class, 'patient_visit_id');
    }

    public function counter()
    {
        return $this->belongsTo(QueueCounter::class, 'queue_counter_id');
    }

    public function assignments()
    {
        return $this->hasMany(QueueRoomAssignment::class, 'queue_ticket_id');
    }

    // Alias used in some places
    public function roomAssignments()
    {
        return $this->hasMany(QueueRoomAssignment::class, 'queue_ticket_id');
    }

    public function issuedBy()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function currentRoom()
    {
        return $this->hasOne(QueueRoomAssignment::class)
                    ->whereIn('status', ['waiting', 'directing', 'calling', 'serving'])
                    ->orderBy('routing_sequence');
    }


    // ── Auto-generate ticket number ────────────────────

    protected static function booted(): void
    {
        static::creating(function (QueueTicket $ticket) {
            if (empty($ticket->ticket_number)) {
                $ticket->ticket_number = static::generateTicketNumber($ticket->queue_counter_id);
            }
        });
    }

    protected static function generateTicketNumber(int $counterId): string
    {
        $counter = \App\Models\QueueCounter::find($counterId);
        $prefix  = $counter?->counter_code ?? 'A';

        // Fix H — FOR UPDATE lock prevents two concurrent creates from getting the same number.
        // This must run inside a transaction (issueTicket wraps everything in DB::transaction).
        $result = \Illuminate\Support\Facades\DB::selectOne(
            "SELECT COALESCE(
                MAX(CAST(SUBSTRING_INDEX(ticket_number, '-', -1) AS UNSIGNED)),
            0) as max_num
            FROM queue_tickets
            WHERE ticket_number LIKE ? FOR UPDATE",
            [$prefix . '-%']
        );

        $next = (int)($result->max_num ?? 0) + 1;
        return $prefix . '-' . str_pad($next, 3, '0', STR_PAD_LEFT);
    }

    // ── Scopes ─────────────────────────────────────────

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['waiting', 'routing', 'in_progress']);
    }

    public function scopeForRoom($query, string $room)
    {
        return $query->whereHas('roomAssignments', fn($q) =>
            $q->where('room', $room)
              ->whereIn('status', ['waiting', 'calling', 'serving'])
        );
    }

    // ── Helpers ────────────────────────────────────────

    public function getPriorityWeightAttribute(): int
    {
        return match($this->priority) {
            'urgent'   => 5,
            'pwd'      => 4,
            'pregnant' => 4,
            'senior'   => 3,
            default    => 1,
        };
    }

}
