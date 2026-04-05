<?php

namespace App\Http\Controllers;

use App\Models\KioskCheckIn;
use App\Models\Patient;
use App\Models\PatientVisit;
use App\Models\QueueCounter;
use App\Models\QueueRoomAssignment;
use App\Models\QueueTicket;
use App\Services\RoomRoutingEngine;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class QueueController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private RoomRoutingEngine $engine) {}

    // ── RECEPTIONIST QUEUE DASHBOARD ──────────────────

    public function index(Request $request)
    {
        $today = today();

        // Today's tickets with patient + room assignments
        $tickets = QueueTicket::with([
            'patient',
            'counter',
            'roomAssignments' => fn($q) => $q->orderBy('routing_sequence'),
        ])
        ->today()
        ->when($request->filled('status'), fn($q) =>
            $q->where('status', $request->status)
        )
        ->when($request->filled('priority'), fn($q) =>
            $q->where('priority', $request->priority)
        )
        ->orderByRaw("FIELD(priority, 'urgent','pregnant','pwd','senior','regular')")
        ->orderBy('issued_at')
        ->get()
        ->map(fn($t) => [
            'id'             => $t->id,
            'ticket_number'  => $t->ticket_number,
            'patient_name'   => $t->patient->full_name,
            'patient_code'   => $t->patient->patient_code,
            'visit_type'     => $t->visit_type,
            'priority'       => $t->priority,
            'status'         => $t->status,
            'issued_at'      => $t->issued_at->format('h:i A'),
            'services'       => $t->services_requested ?? [],
            'rooms'              => $t->roomAssignments->map(fn($r) => [
                'room'         => $r->room,
                'room_label'   => $r->room_label,
                'queue_number' => $r->queue_number,
                'sequence'     => $r->routing_sequence,
                'status'       => $r->status,
                'is_gated'     => in_array($r->room, RoomRoutingEngine::GATED_ROOMS),
            ]),
            // For parallel mode: how many parallel rooms are still pending
            'parallel_pending'   => $t->roomAssignments
                ->whereNotIn('room', RoomRoutingEngine::GATED_ROOMS)
                ->whereNotIn('status', ['completed', 'no_show', 'skipped'])
                ->count(),
            // Is the interview room currently locked waiting for parallel rooms?
            'interview_locked'   => $t->roomAssignments
                ->where('room', 'interview_room')
                ->where('status', 'locked')
                ->isNotEmpty(),
            'current_room'       => $t->roomAssignments
                ->whereIn('status', ['calling','serving'])
                ->sortBy('routing_sequence')
                ->first()?->room_label ?? '—',
        ]);

        // Room stats
        $roomStats = $this->engine->getRoomStats();

        // Summary counts
        $summary = [
            'total'       => QueueTicket::today()->count(),
            'waiting'     => QueueTicket::today()->where('status', 'waiting')->count(),
            'in_progress' => QueueTicket::today()->where('status', 'in_progress')->count(),
            'completed'   => QueueTicket::today()->where('status', 'completed')->count(),
            'no_show'     => QueueTicket::today()->where('status', 'no_show')->count(),
        ];

        // All counters (so admins can see and manage inactive ones too)
        $counters = QueueCounter::orderBy('id')->get()->map(fn($c) => [
            'id'           => $c->id,
            'counter_name' => $c->counter_name,
            'counter_code' => $c->counter_code,
            'is_active'    => $c->is_active,
        ]);

        return inertia('Queue/Index', compact(
            'tickets', 'roomStats', 'summary', 'counters'
        ));
    }

        // ── ISSUE TICKET ──────────────────────────────────
    public function issueTicket(Request $request)
    {
        $validated = $request->validate([
            'patient_id'         => ['required', 'exists:patients,id'],
            'visit_type'         => ['required', 'in:opd,pre_employment,annual_pe,exit_pe,follow_up,lab_only'],
            'priority'           => ['required', 'in:regular,senior,pwd,pregnant,urgent'],
            'queue_counter_id'   => ['nullable', 'exists:queue_counters,id'],
            'services_requested' => ['required', 'array', 'min:1'],
            'employer_company'   => ['nullable', 'string', 'max:150'],
            'chief_complaint'    => ['nullable', 'string'],
        ]);

        // Auto-assign first active counter when kiosk doesn't supply one
        if (empty($validated['queue_counter_id'])) {
            $counter = QueueCounter::active()->first();
            if (!$counter) {
                return back()->withErrors([
                    'queue_counter_id' => 'No active counter is open right now. Please ask a staff member for assistance.',
                ]);
            }
            $validated['queue_counter_id'] = $counter->id;
        }

        // Fix F — Prevent duplicate active tickets for the same patient today
        $existingTicket = QueueTicket::today()
            ->where('patient_id', $validated['patient_id'])
            ->whereIn('status', ['waiting', 'in_progress'])
            ->first();

        if ($existingTicket) {
            return back()->withErrors([
                'patient_id' => "This patient already has an active ticket today ({$existingTicket->ticket_number}). Cancel it first before issuing a new one.",
            ]);
        }

        // Fix A — Wrap everything in a transaction; rollback if routing fails
        [$ticket, $roomCount, $rooms] = DB::transaction(function () use ($validated) {

            // Step 1 — Create patient visit
            $visit = PatientVisit::create([
                'patient_id'        => $validated['patient_id'],
                'visit_type'        => $validated['visit_type'],
                'employer_company'  => $validated['employer_company'] ?? null,
                'services_selected' => $validated['services_requested'],
                'visit_date'        => now(),
                'status'            => 'pending',
                'chief_complaint'   => $validated['chief_complaint'] ?? null,
                'created_by'        => Auth::id(),
            ]);

            // Step 2 — Issue queue ticket
            $ticket = QueueTicket::create([
                'patient_id'         => $validated['patient_id'],
                'patient_visit_id'   => $visit->id,
                'queue_counter_id'   => $validated['queue_counter_id'],
                'visit_type'         => $validated['visit_type'],
                'priority'           => $validated['priority'],
                'services_requested' => $validated['services_requested'],
                'issued_by'          => Auth::id(),
                'issued_at'          => now(),
            ]);

            // Step 3 — Route to rooms via engine (inside transaction — rollback if fails)
            $this->engine->route($ticket);

            // Step 4 — Get routing summary
            $ticket->refresh();
            $ticket->load('roomAssignments');
            $routing   = $this->engine->getRoutingSummary($ticket);
            $roomCount = count($routing);
            $rooms     = $ticket->roomAssignments->map(fn($a) => [
                'room'         => $a->room,
                'queue_number' => $a->queue_number,
            ])->values()->toArray();

            return [$ticket, $roomCount, $rooms];
        });

        $patient = $ticket->patient;

        return back()
            ->with('success', "Ticket {$ticket->ticket_number} issued! Routed to {$roomCount} room(s).")
            ->with('newTicket', [
                'ticket_number'  => $ticket->ticket_number,
                'patient_name'   => $patient->full_name,
                'patient_code'   => $patient->patient_code,
                'visit_type'     => $ticket->visit_type,
                'priority'       => $ticket->priority,
                'services'       => $ticket->services_requested ?? [],
                'rooms'          => $rooms,
                'issued_at'      => $ticket->issued_at->format('M d, Y h:i A'),
            ]);
    }

    // ── ROOM SCREEN ───────────────────────────────────

    public function roomScreen(string $room)
    {
        $validRooms = ['laboratory', 'xray_utz', 'drug_test', 'nurse_station', 'interview_room'];

        if (!in_array($room, $validRooms)) {
            abort(404, 'Invalid room.');
        }

        $queue = QueueRoomAssignment::with([
            'ticket.patient',
            'ticket.roomAssignments',
        ])
        ->today()
        ->forRoom($room)
        ->whereIn('status', ['waiting', 'calling', 'serving'])
        ->orderByRaw("FIELD(priority, 'urgent','pregnant','pwd','senior','regular') DESC")
        ->orderBy('created_at')
        ->get()
        ->map(fn($a) => [
            'id'             => $a->id,
            'queue_number'   => $a->queue_number,
            'patient_name'   => $a->ticket->patient->full_name,
            'patient_code'   => $a->ticket->patient->patient_code,
            'age_sex'        => $a->ticket->patient->age_sex,
            'visit_type'     => $a->ticket->visit_type,
            'priority'       => $a->priority,
            'status'         => $a->status,
            'call_count'     => $a->call_count,
            'sequence'       => $a->routing_sequence,
            'services'       => $a->ticket->services_requested ?? [],
            'issued_at'      => $a->ticket->issued_at->format('h:i A'),
            // Cross-room awareness: show which other room is currently serving this patient
            'serving_in'     => $a->ticket->roomAssignments
                ->where('status', 'serving')
                ->where('id', '!=', $a->id)
                ->map(fn($r) => QueueRoomAssignment::ROOM_LABELS[$r->room] ?? $r->room)
                ->first(),
        ]);

        $roomLabel = QueueRoomAssignment::ROOM_LABELS[$room];

        return inertia('Queue/RoomScreen', compact('queue', 'room', 'roomLabel'));
    }

    // ── TV DISPLAY BOARD ──────────────────────────────

    public function display()
    {
        $rooms = ['laboratory', 'xray_utz', 'drug_test', 'nurse_station', 'interview_room'];
        $board = [];

        foreach ($rooms as $room) {
            $serving = QueueRoomAssignment::with('ticket.patient')
                ->today()
                ->forRoom($room)
                ->where('status', 'serving')
                ->orderBy('served_at', 'desc')
                ->first();

            $calling = QueueRoomAssignment::with('ticket.patient')
                ->today()
                ->forRoom($room)
                ->where('status', 'calling')
                ->orderBy('called_at', 'desc')
                ->first();

            $waitCount = QueueRoomAssignment::today()
                ->forRoom($room)
                ->where('status', 'waiting')
                ->count();

            $board[$room] = [
                'label'      => QueueRoomAssignment::ROOM_LABELS[$room],
                'serving'    => $serving ? [
                    'queue_number' => $serving->queue_number,
                    'patient_name' => $serving->ticket->patient->full_name,
                ] : null,
                'calling'    => $calling ? [
                    'queue_number' => $calling->queue_number,
                    'patient_name' => $calling->ticket->patient->full_name,
                ] : null,
                'wait_count' => $waitCount,
            ];
        }

        $todayTotal     = QueueTicket::today()->count();
        $todayCompleted = QueueTicket::today()->where('status', 'completed')->count();

        return inertia('Queue/Display', compact('board', 'todayTotal', 'todayCompleted'));
    }

    // ── CALL NEXT ─────────────────────────────────────

    public function callNext(Request $request)
    {
        $request->validate([
            'room' => ['required', 'in:laboratory,xray_utz,drug_test,nurse_station,interview_room'],
        ]);

        $next = QueueRoomAssignment::with('ticket.patient')
            ->today()
            ->forRoom($request->room)
            ->where('status', 'waiting')
            ->orderByRaw("FIELD(priority,'urgent','pregnant','pwd','senior','regular') DESC")
            ->orderBy('created_at')
            ->first();

        if (!$next) {
            return back()->with('error', 'No patients waiting in this room.');
        }

        // Cross-room check — warn if this patient is currently being served elsewhere
        $servingElsewhere = QueueRoomAssignment::where('queue_ticket_id', $next->queue_ticket_id)
            ->where('id', '!=', $next->id)
            ->where('status', 'serving')
            ->first();

        if ($servingElsewhere) {
            $otherRoom = QueueRoomAssignment::ROOM_LABELS[$servingElsewhere->room] ?? $servingElsewhere->room;
            return back()->with('error',
                "Cannot call {$next->queue_number} — patient is currently being served at {$otherRoom}. Wait for them to be released."
            );
        }

        // Fix E — atomic increment to prevent race condition on concurrent calls
        $next->update([
            'status'     => 'calling',
            'called_at'  => now(),
            'call_count' => DB::raw('call_count + 1'),
        ]);

        return back()->with('success',
            "Calling {$next->queue_number} — {$next->ticket->patient->full_name}"
        );
    }

    // ── MARK SERVING ──────────────────────────────────

    public function markServing(QueueRoomAssignment $assignment)
    {
        // Cross-room check — block if patient is already being served in another room
        $servingElsewhere = QueueRoomAssignment::where('queue_ticket_id', $assignment->queue_ticket_id)
            ->where('id', '!=', $assignment->id)
            ->where('status', 'serving')
            ->first();

        if ($servingElsewhere) {
            $otherRoom = QueueRoomAssignment::ROOM_LABELS[$servingElsewhere->room] ?? $servingElsewhere->room;
            return back()->with('error',
                "Cannot mark serving — patient is currently being served at {$otherRoom}."
            );
        }

        $assignment->update([
            'status'   => 'serving',
            'served_at'=> now(),
            'served_by'=> Auth::id(),
        ]);

        return back()->with('success', "Now serving {$assignment->queue_number}");
    }

    // ── MARK COMPLETE ─────────────────────────────────

    public function markComplete(QueueRoomAssignment $assignment)
    {
        $nextRoom = $this->engine->completeRoom($assignment);

        $message = $nextRoom
            ? "Completed. Patient directed to {$nextRoom->room_label} ({$nextRoom->queue_number})"
            : "All rooms completed. Visit closed.";

        return back()->with('success', $message);
    }

    // ── MARK NO SHOW ──────────────────────────────────

    public function markNoShow(QueueRoomAssignment $assignment)
    {
        $ticket = $assignment->ticket;

        DB::transaction(function () use ($assignment, $ticket) {
            // Mark this room as no-show
            $assignment->update(['status' => 'no_show']);

            // Fix D — also mark all remaining waiting/calling rooms as no_show
            // so the ticket doesn't stay stuck in in_progress forever
            $ticket->roomAssignments()
                ->whereIn('status', ['waiting', 'calling'])
                ->update(['status' => 'no_show']);

            // Ticket is now fully closed
            $ticket->update([
                'status'       => 'no_show',
                'completed_at' => now(),
            ]);
        });

        return back()->with('success', "Marked as no-show. Remaining rooms cleared.");
    }

    // ── SKIP ──────────────────────────────────────────

    public function skip(QueueRoomAssignment $assignment)
    {
        $ticket = $assignment->ticket;

        DB::transaction(function () use ($assignment, $ticket) {
            $assignment->update(['status' => 'skipped']);

            // Skipping a parallel room may unlock the interview room gate
            $this->engine->checkAndUnlockGatedRooms($ticket);

            // If every room is now done, close the ticket
            $allDone = $ticket->roomAssignments()
                ->whereNotIn('status', ['completed', 'no_show', 'skipped'])
                ->doesntExist();

            if ($allDone) {
                $ticket->update([
                    'status'       => 'completed',
                    'completed_at' => now(),
                ]);
            }
        });

        return back()->with('success', "Patient skipped.");
    }

    // ── RE-CALL ───────────────────────────────────────

    public function recall(QueueRoomAssignment $assignment)
    {
        // Fix G — do not allow recalling a patient already being served
        if ($assignment->status === 'serving') {
            return back()->with('error',
                "Cannot re-call {$assignment->queue_number} — patient is already being served."
            );
        }

        // Fix E — atomic increment
        $assignment->update([
            'status'     => 'calling',
            'called_at'  => now(),
            'call_count' => DB::raw('call_count + 1'),
        ]);

        $assignment->refresh();

        return back()->with('success',
            "Re-calling {$assignment->queue_number} (Call #{$assignment->call_count})"
        );
    }

    // ── CANCEL TICKET ─────────────────────────────────

    public function cancelTicket(QueueTicket $ticket)
    {
        $ticket->update([
            'status'       => 'cancelled',
            'completed_at' => now(),
        ]);

        // Fix C — include 'serving' so in-progress rooms are also cleared on cancel
        $ticket->roomAssignments()
            ->whereIn('status', ['waiting', 'calling', 'serving', 'directing'])
            ->update(['status' => 'skipped']);

        return back()->with('success', "Ticket {$ticket->ticket_number} cancelled.");
    }

    // ── COUNTER MANAGEMENT ────────────────────────────

    public function storeCounter(Request $request)
    {
        $request->validate([
            'counter_name' => ['required', 'string', 'max:50'],
            'counter_code' => ['required', 'string', 'max:10', 'unique:queue_counters,counter_code'],
        ]);

        QueueCounter::create([
            'counter_name' => $request->counter_name,
            'counter_code' => strtoupper($request->counter_code),
            'is_active'    => true,
        ]);

        return back()->with('success', "Counter \"{$request->counter_name}\" created.");
    }

    public function toggleCounter(QueueCounter $counter)
    {
        $counter->update(['is_active' => !$counter->is_active]);
        $state = $counter->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "Counter \"{$counter->counter_name}\" {$state}.");
    }

    public function destroyCounter(QueueCounter $counter)
    {
        $counter->delete();
        return back()->with('success', "Counter \"{$counter->counter_name}\" deleted.");
    }

    // ── KIOSK ─────────────────────────────────────────

    public function kiosk()
    {
        // Pass active services grouped by category so the kiosk always reflects
        // the live service catalog — no hardcoded list on the frontend.
        $services = \App\Models\ServiceCatalog::active()
            ->orderBy('category')
            ->orderBy('service_name')
            ->get()
            ->groupBy('category')
            ->map(fn($items, $category) => [
                'category' => $category,
                'room'     => $items->first()->room,
                'services' => $items->map(fn($s) => [
                    'code'  => $s->service_code,
                    'label' => $s->service_name,
                ])->values(),
            ])
            ->values();

        return inertia('Queue/Kiosk', compact('services'));
    }

    // ── KIOSK CHECK-IN (pre-registration only — no ticket issued) ──

    public function kioskCheckIn(Request $request)
    {
        $validated = $request->validate([
            'patient_id'         => ['required', 'exists:patients,id'],
            'visit_type'         => ['required', 'in:opd,pre_employment,annual_pe,exit_pe,follow_up,lab_only'],
            'priority'           => ['required', 'in:regular,senior,pwd,pregnant,urgent'],
            'services_requested' => ['required', 'array', 'min:1'],
            'employer_company'   => ['nullable', 'string', 'max:150'],
            'chief_complaint'    => ['nullable', 'string'],
        ]);

        // Prevent duplicate pending check-ins for the same patient today
        $existing = KioskCheckIn::where('patient_id', $validated['patient_id'])
            ->where('status', 'pending')
            ->whereDate('created_at', today())
            ->first();

        if ($existing) {
            return back()->withErrors([
                'patient_id' => 'You already have a pending check-in today. Please proceed to the Reception Counter.',
            ]);
        }

        $checkin = KioskCheckIn::create($validated);
        $patient = Patient::find($validated['patient_id']);

        return back()->with('kioskSuccess', [
            'checkin_id'    => $checkin->id,
            'patient_name'  => $patient->full_name,
            'patient_code'  => $patient->patient_code,
            'visit_type'    => $validated['visit_type'],
            'priority'      => $validated['priority'],
            'services'      => $validated['services_requested'],
            'checked_in_at' => $checkin->created_at->format('M d, Y h:i A'),
        ]);
    }

    // ── CANCEL KIOSK CHECK-IN (reception can dismiss) ──

    public function cancelKioskCheckIn(KioskCheckIn $checkin)
    {
        $checkin->update(['status' => 'cancelled']);
        return back()->with('success', 'Kiosk check-in cancelled.');
    }

    // ── SEARCH PATIENT FOR TICKET FORM ────────────────

    public function searchPatient(Request $request)
    {
        $patients = Patient::active()
            ->search($request->q)
            ->limit(8)
            ->get()
            ->map(fn($p) => [
                'id'           => $p->id,
                'full_name'    => $p->full_name,
                'patient_code' => $p->patient_code,
                'age_sex'      => $p->age_sex,
                'visit_type'   => $p->visit_type_default,
            ]);

        return response()->json($patients);
    }
}
