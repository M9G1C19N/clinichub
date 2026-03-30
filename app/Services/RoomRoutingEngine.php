<?php

namespace App\Services;

use App\Models\QueueTicket;
use App\Models\QueueRoomAssignment;
use App\Models\PatientVisit;
use Illuminate\Support\Facades\DB;

class RoomRoutingEngine
{
    // Map service codes to rooms
    const SERVICE_ROOM_MAP = [
        // Laboratory tests
        'CBC'         => 'laboratory',
        'UA'          => 'laboratory',
        'FECALYSIS'   => 'laboratory',
        'BLOODTYPING' => 'laboratory',
        'FBS'         => 'laboratory',
        'BUN'         => 'laboratory',
        'CREATININE'  => 'laboratory',
        'URICACID'    => 'laboratory',
        'CHOLESTEROL' => 'laboratory',
        'TRIGLYCERIDES'=> 'laboratory',
        'HDLLDL'      => 'laboratory',
        'SGOT'        => 'laboratory',
        'SGPT'        => 'laboratory',
        'HBSAG'       => 'laboratory',
        'VDRL'        => 'laboratory',
        'PREGNANCY'   => 'laboratory',
        'DENGUE'      => 'laboratory',
        'THYROID'     => 'laboratory',

        // X-Ray & Ultrasound
        'CXRPA'       => 'xray_utz',
        'XRAY'        => 'xray_utz',
        'UTZ'         => 'xray_utz',
        'ECG'         => 'xray_utz',

        // Drug Test — ALWAYS FIRST (urine specimen time-sensitive)
        'DRUGTEST'    => 'drug_test',
        'MET'         => 'drug_test',
        'THC'         => 'drug_test',

        // Interview Room (doctor consultation)
        'OPD'         => 'interview_room',
        'CONSULTATION'=> 'interview_room',
    ];

    const ROOM_LABELS = [
    'laboratory'     => 'Laboratory',
    'xray_utz'       => 'X-Ray & Ultrasound',
    'drug_test'      => 'Drug Test',
    'interview_room' => 'Interview Room',
];

    /**
     * Main routing method.
     * Takes a QueueTicket and assigns it to rooms in optimal sequence.
     */
    public function route(QueueTicket $ticket): array
    {
        $services = $ticket->services_requested ?? [];

        // 1. Determine which rooms are needed
        $roomsNeeded = $this->determineRoomsNeeded($services, $ticket->visit_type);

        if (empty($roomsNeeded)) {
            return [];
        }

        // 2. Get current load for each room
        $roomLoads = $this->getRoomLoads($roomsNeeded);

        // 3. Calculate optimal sequence
        $sequence = $this->calculateSequence($roomsNeeded, $roomLoads);

        // 4. Create room assignments in DB
        $assignments = $this->createAssignments($ticket, $sequence, $roomLoads);

        // 5. Update ticket status
        $ticket->update(['status' => 'in_progress']);

        return $assignments;
    }

    /**
     * Determine which rooms are needed based on services + visit type.
     */
    private function determineRoomsNeeded(array $services, string $visitType): array
    {
        $rooms = [];

        foreach ($services as $serviceCode) {
            $room = self::SERVICE_ROOM_MAP[strtoupper($serviceCode)] ?? null;
            if ($room && !in_array($room, $rooms)) {
                $rooms[] = $room;
            }
        }

        // OPD always needs interview room
        if ($visitType === 'opd' && !in_array('interview_room', $rooms)) {
            $rooms[] = 'interview_room';
        }

        return $rooms;
    }

    /**
     * Get current active queue count per room.
     */
    private function getRoomLoads(array $rooms): array
    {
        $loads = [];

        foreach ($rooms as $room) {
            $loads[$room] = QueueRoomAssignment::today()
                ->forRoom($room)
                ->active()
                ->count();
        }

        return $loads;
    }

    /**
     * Calculate optimal visit sequence.
     *
     * RULES (from spec):
     * 1. Drug test ALWAYS first — urine specimen is time-sensitive
     * 2. Then shortest queue first
     * 3. Equal load = alphabetical room name as tiebreaker
     */
    private function calculateSequence(array $rooms, array $loads): array
    {
        // Separate drug test from other rooms
        $drugTest   = in_array('drug_test', $rooms) ? ['drug_test'] : [];
        $otherRooms = array_filter($rooms, fn($r) => $r !== 'drug_test');

        // Sort other rooms by load (ascending), then alphabetically as tiebreaker
        usort($otherRooms, function ($a, $b) use ($loads) {
            $loadDiff = ($loads[$a] ?? 0) - ($loads[$b] ?? 0);
            return $loadDiff !== 0 ? $loadDiff : strcmp($a, $b);
        });

        // Drug test always first, then sorted rooms
        return array_values(array_merge($drugTest, $otherRooms));
    }

    /**
     * Create QueueRoomAssignment records in DB.
     */
    private function createAssignments(
        QueueTicket $ticket,
        array $sequence,
        array $roomLoads
    ): array {
        $assignments = [];

        DB::transaction(function () use ($ticket, $sequence, $roomLoads, &$assignments) {
            foreach ($sequence as $index => $room) {
                $assignment = QueueRoomAssignment::create([
                    'queue_ticket_id'     => $ticket->id,
                    'patient_visit_id'    => $ticket->patient_visit_id,
                    'room'                => $room,
                    'queue_number'        => QueueRoomAssignment::generateRoomNumber($room),
                    'routing_sequence'    => $index + 1,
                    'room_load_at_routing'=> $roomLoads[$room] ?? 0,
                    'priority'            => $ticket->priority,
                    'status'              => $index === 0 ? 'waiting' : 'waiting',
                ]);

                $assignments[] = $assignment;
            }
        });

        return $assignments;
    }

    /**
     * Get routing summary for display.
     */
    public function getRoutingSummary(QueueTicket $ticket): array
    {
        return $ticket->roomAssignments()
            ->orderBy('routing_sequence')
            ->get()
            ->map(fn($a) => [
                'sequence'     => $a->routing_sequence,
                'room'         => $a->room,
                'room_label'   => $a->room_label,
                'queue_number' => $a->queue_number,
                'status'       => $a->status,
                'load_at_time' => $a->room_load_at_routing,
            ])
            ->toArray();
    }

    /**
     * Mark a room assignment as completed and activate next room.
     */
    public function completeRoom(QueueRoomAssignment $assignment): ?QueueRoomAssignment
    {
        $assignment->update([
            'status'       => 'completed',
            'completed_at' => now(),
        ]);

        // Find next room in sequence
        $nextAssignment = QueueRoomAssignment::where('queue_ticket_id', $assignment->queue_ticket_id)
            ->where('routing_sequence', $assignment->routing_sequence + 1)
            ->where('status', 'waiting')
            ->first();

        if ($nextAssignment) {
            $nextAssignment->update(['status' => 'waiting']);
            return $nextAssignment;
        }

        // No more rooms — complete the ticket
        $assignment->ticket->update([
            'status'       => 'completed',
            'completed_at' => now(),
        ]);

        return null;
    }

    /**
     * Get room statistics for the queue dashboard.
     */
    public function getRoomStats(): array
    {
        $rooms = ['laboratory', 'xray_utz', 'drug_test', 'interview_room'];
        $stats = [];

        foreach ($rooms as $room) {
            $stats[$room] = [
                'label'     => self::ROOM_LABELS[$room],  // ← use self:: not QueueRoomAssignment::
                'waiting'   => QueueRoomAssignment::today()->forRoom($room)->where('status', 'waiting')->count(),
                'serving'   => QueueRoomAssignment::today()->forRoom($room)->where('status', 'serving')->count(),
                'completed' => QueueRoomAssignment::today()->forRoom($room)->where('status', 'completed')->count(),
                'no_show'   => QueueRoomAssignment::today()->forRoom($room)->where('status', 'no_show')->count(),
            ];
        }

        return $stats;
    }

}
