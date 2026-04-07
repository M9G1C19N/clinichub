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
        'RBS'         => 'laboratory',
        'PSA'         => 'laboratory',
        'HBA1C'       => 'laboratory',
        'OGTT'        => 'laboratory',

        // X-Ray & Ultrasound
        'CXRPA'       => 'xray_utz',
        'XRAY'        => 'xray_utz',
        'UTZ'         => 'xray_utz',
        'UTZ_ABDOMEN' => 'xray_utz',
        'UTZ_KUB'     => 'xray_utz',
        'UTZ_PELVIS'  => 'xray_utz',
        'ECG'         => 'xray_utz',

        'BLOOD_CHEMISTRY' => 'laboratory',

        // Drug Test — ALWAYS FIRST (urine specimen time-sensitive)
        'DRUGTEST'    => 'drug_test',
        'DRUGTEST5'   => 'drug_test',
        'MET'         => 'drug_test',
        'THC'         => 'drug_test',

        // Interview Room (doctor consultation)
        'OPD'         => 'interview_room',
        'CONSULTATION'=> 'interview_room',
        'PE_CONSULT' => 'interview_room',
        'ANNUAL_PE'  => 'interview_room',
        'EXIT_PE'    => 'interview_room',
        'FOLLOW_UP'  => 'interview_room',
    ];

    const ROOM_LABELS = [
        'laboratory'     => 'Laboratory',
        'xray_utz'       => 'X-Ray & Ultrasound',
        'drug_test'      => 'Drug Test',
        'nurse_station'  => 'Nurse Station',
        'interview_room' => 'Interview Room (Doctor)',
    ];

    // GATED rooms stay locked until ALL parallel rooms are done.
    // nurse_station is PARALLEL (unlocks immediately with lab/xray/drug_test).
    // interview_room (doctor) is GATED — only unlocks after nurse + all diagnostic rooms complete.
    const GATED_ROOMS = ['interview_room'];

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

        // Visit types that require nurse intake + doctor consultation
        $needsNurseAndDoctor = ['pre_employment', 'annual_pe', 'exit_pe', 'opd', 'follow_up'];
        if (in_array($visitType, $needsNurseAndDoctor)) {
            // Nurse station: parallel (unlocks immediately alongside lab/xray/drug test)
            if (!in_array('nurse_station', $rooms)) {
                $rooms[] = 'nurse_station';
            }
            // Interview room (doctor): gated — only unlocks after nurse + all diagnostic rooms done
            if (!in_array('interview_room', $rooms)) {
                $rooms[] = 'interview_room';
            }
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
     * Calculate display sequence for routing.
     *
     * PARALLEL MODE RULES:
     * 1. Drug test FIRST (always) — urine specimen is time-sensitive
     * 2. Lab + X-Ray — PARALLEL (both unlock immediately, patient goes to shorter queue)
     * 3. Interview Room (Nurse → Doctor) — LAST, GATED (stays locked until all parallel rooms done)
     *
     * Sorting within parallel group = shortest queue first (load balancing).
     */
    private function calculateSequence(array $rooms, array $loads): array
    {
        $drugTest      = in_array('drug_test', $rooms) ? ['drug_test'] : [];
        $gatedRooms    = array_values(array_filter($rooms, fn($r) =>  in_array($r, self::GATED_ROOMS)));
        $parallelRooms = array_values(array_filter($rooms, fn($r) =>
            $r !== 'drug_test' && !in_array($r, self::GATED_ROOMS)
        ));

        // Sort parallel rooms by current load so lower-load rooms appear first on the slip
        usort($parallelRooms, function ($a, $b) use ($loads) {
            $loadDiff = ($loads[$a] ?? 0) - ($loads[$b] ?? 0);
            return $loadDiff !== 0 ? $loadDiff : strcmp($a, $b);
        });

        // Drug test → parallel rooms → gated rooms (interview room always last)
        return array_values(array_merge($drugTest, $parallelRooms, $gatedRooms));
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
                    // PARALLEL MODE: all non-gated rooms start as 'waiting' immediately.
                    // Gated rooms (interview_room) start 'locked' and only unlock
                    // once ALL parallel rooms are completed / skipped / no-show.
                    'status'              => in_array($room, self::GATED_ROOMS) ? 'locked' : 'waiting',
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
     * Mark a room assignment as completed.
     * In parallel mode: checks if all parallel rooms are done → unlocks interview room.
     * If all rooms including interview room are done → closes the ticket.
     */
    public function completeRoom(QueueRoomAssignment $assignment): ?QueueRoomAssignment
    {
        $assignment->update([
            'status'       => 'completed',
            'completed_at' => now(),
        ]);

        $ticket = $assignment->ticket;

        // Try to unlock the gated interview room if all parallel rooms are now done
        $unlockedRoom = $this->checkAndUnlockGatedRooms($ticket);
        if ($unlockedRoom) {
            return $unlockedRoom;
        }

        // Check if every room (including gated) is fully done
        $allDone = $ticket->roomAssignments()
            ->whereNotIn('status', ['completed', 'no_show', 'skipped'])
            ->doesntExist();

        if ($allDone) {
            $ticket->update([
                'status'       => 'completed',
                'completed_at' => now(),
            ]);
        }

        return null;
    }

    /**
     * Check if all parallel (non-gated) rooms for a ticket are done.
     * If so, unlock the gated rooms (interview_room → 'waiting').
     * Returns the first unlocked assignment, or null if nothing changed.
     */
    public function checkAndUnlockGatedRooms(QueueTicket $ticket): ?QueueRoomAssignment
    {
        // Are there any parallel rooms still in an active state?
        $parallelStillPending = $ticket->roomAssignments()
            ->whereNotIn('room', self::GATED_ROOMS)
            ->whereNotIn('status', ['completed', 'no_show', 'skipped'])
            ->exists();

        if ($parallelStillPending) {
            return null; // Parallel rooms not all done yet — keep interview room locked
        }

        // All parallel rooms done — unlock any gated rooms that are still locked
        $lockedGated = $ticket->roomAssignments()
            ->whereIn('room', self::GATED_ROOMS)
            ->where('status', 'locked')
            ->get();

        if ($lockedGated->isEmpty()) {
            return null; // No locked gated rooms (already unlocked, or no interview room)
        }

        $ticket->roomAssignments()
            ->whereIn('room', self::GATED_ROOMS)
            ->where('status', 'locked')
            ->update(['status' => 'waiting']);

        return $lockedGated->first();
    }

    /**
     * Get room statistics for the queue dashboard.
     */
    public function getRoomStats(): array
    {
        $rooms = ['laboratory', 'xray_utz', 'drug_test', 'nurse_station', 'interview_room'];
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
