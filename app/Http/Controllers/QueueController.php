<?php

namespace App\Http\Controllers;

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
            'rooms'          => $t->roomAssignments->map(fn($r) => [
                'room'         => $r->room,
                'room_label'   => $r->room_label,
                'queue_number' => $r->queue_number,
                'sequence'     => $r->routing_sequence,
                'status'       => $r->status,
            ]),
            'current_room'   => $t->roomAssignments
                ->whereIn('status', ['waiting','calling','serving'])
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

        // Active counters
        $counters = QueueCounter::active()->get()->map(fn($c) => [
            'id'           => $c->id,
            'counter_name' => $c->counter_name,
            'counter_code' => $c->counter_code,
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
            'visit_type' => ['required', 'in:opd,pre_employment,annual_pe,exit_pe,follow_up,lab_only'],
            'priority'           => ['required', 'in:regular,senior,pwd,pregnant,urgent'],
            'queue_counter_id'   => ['required', 'exists:queue_counters,id'],
            'services_requested' => ['required', 'array', 'min:1'],
            'employer_company'   => ['nullable', 'string', 'max:150'],
            'chief_complaint'    => ['nullable', 'string'],
        ]);

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

        // Step 3 — Route to rooms via engine
        $this->engine->route($ticket);

        // Step 4 — Get routing summary
        $ticket->refresh();
        $ticket->load('roomAssignments');
        $routing = $this->engine->getRoutingSummary($ticket);
        $roomCount = count($routing);

        return back()->with('success',
            "Ticket {$ticket->ticket_number} issued! Routed to {$roomCount} room(s)."
        );
    }

    // ── ROOM SCREEN ───────────────────────────────────

    public function roomScreen(string $room)
    {
        $validRooms = ['laboratory', 'xray_utz', 'drug_test', 'interview_room'];

        if (!in_array($room, $validRooms)) {
            abort(404, 'Invalid room.');
        }

        $queue = QueueRoomAssignment::with([
            'ticket.patient',
            'ticket' => fn($q) => $q->with('patient'),
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
        ]);

        $roomLabel = QueueRoomAssignment::ROOM_LABELS[$room];

        return inertia('Queue/RoomScreen', compact('queue', 'room', 'roomLabel'));
    }

    // ── TV DISPLAY BOARD ──────────────────────────────

    public function display()
    {
        $rooms = ['laboratory', 'xray_utz', 'drug_test', 'interview_room'];
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
            'room' => ['required', 'in:laboratory,xray_utz,drug_test,interview_room'],
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

        $next->update([
            'status'     => 'calling',
            'called_at'  => now(),
            'call_count' => $next->call_count + 1,
        ]);

        return back()->with('success',
            "Calling {$next->queue_number} — {$next->ticket->patient->full_name}"
        );
    }

    // ── MARK SERVING ──────────────────────────────────

    public function markServing(QueueRoomAssignment $assignment)
    {
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
        $assignment->update(['status' => 'no_show']);

        // Check if all rooms done/no_show
        $ticket     = $assignment->ticket;
        $allDone    = $ticket->roomAssignments()
            ->whereNotIn('status', ['completed', 'no_show', 'skipped'])
            ->doesntExist();

        if ($allDone) {
            $ticket->update([
                'status'       => 'no_show',
                'completed_at' => now(),
            ]);
        }

        return back()->with('success', "Marked as no-show.");
    }

    // ── SKIP ──────────────────────────────────────────

    public function skip(QueueRoomAssignment $assignment)
    {
        $assignment->update(['status' => 'skipped']);
        return back()->with('success', "Patient skipped.");
    }

    // ── RE-CALL ───────────────────────────────────────

    public function recall(QueueRoomAssignment $assignment)
    {
        $assignment->update([
            'status'     => 'calling',
            'called_at'  => now(),
            'call_count' => $assignment->call_count + 1,
        ]);

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

        $ticket->roomAssignments()
            ->whereIn('status', ['waiting', 'calling', 'directing'])
            ->update(['status' => 'skipped']);

        return back()->with('success', "Ticket {$ticket->ticket_number} cancelled.");
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
