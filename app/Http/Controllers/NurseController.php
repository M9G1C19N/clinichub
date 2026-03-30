<?php

namespace App\Http\Controllers;

use App\Models\PatientVital;
use App\Models\PatientVisit;
use App\Models\QueueRoomAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NurseController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search', '');
        $date   = $request->get('date', '');

        // ── TODAY'S QUEUE — include completed so patients
        // don't disappear after queue is marked done ──────
        $todayQueue = QueueRoomAssignment::with([
            'ticket.patient',
            'ticket.visit.vitals',
        ])
        ->today()
        ->forRoom('interview_room')
        ->whereNotIn('status', ['no_show', 'skipped', 'cancelled'])
        ->orderByRaw("FIELD(status, 'serving', 'calling', 'waiting', 'completed')")
        ->orderBy('routing_sequence')
        ->orderBy('created_at')
        ->get()
        ->map(fn($a) => [
            'id'           => $a->id,
            'queue_number' => $a->queue_number,
            'status'       => $a->status,
            'priority'     => $a->ticket?->priority ?? 'regular',
            'patient' => [
                'id'           => $a->ticket?->patient?->id,
                'full_name'    => $a->ticket?->patient?->full_name ?? '—',
                'patient_code' => $a->ticket?->patient?->patient_code ?? '—',
                'age_sex'      => $a->ticket?->patient?->age_sex ?? '—',
            ],
            'visit' => $a->ticket?->patient_visit_id ? [
                'id'               => $a->ticket->visit?->id,
                'visit_type'       => $a->ticket->visit?->visit_type,
                'employer_company' => $a->ticket->visit?->employer_company,
                'has_vitals'       => $a->ticket->visit?->vitals !== null,
            ] : null,
        ]);

        // ── PENDING — visits with no vitals (any date) ───
        $pendingQuery = PatientVisit::with(['patient'])
            ->whereDoesntHave('vitals')
            ->where('status', '!=', 'cancelled')
            ->whereIn('visit_type', ['opd', 'pre_employment', 'follow_up'])
            ->when($search, fn($q) =>
                $q->whereHas('patient', fn($p) =>
                    $p->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('patient_code', 'like', "%{$search}%")
                )
            )
            ->latest('visit_date')
            ->paginate(10, ['*'], 'pending_page')
            ->withQueryString();

        $pending = $pendingQuery->getCollection()->map(fn($v) => [
            'id'           => $v->id,
            'visit_id'     => $v->id,
            'visit_type'   => $v->visit_type,
            'visit_date'   => $v->visit_date->format('M d, Y'),
            'employer'     => $v->employer_company,
            'patient_name' => $v->patient->full_name,
            'patient_code' => $v->patient->patient_code,
            'age_sex'      => $v->patient->age_sex,
        ]);
        $pendingQuery->setCollection($pending);

        // ── HISTORY — visits with vitals (searchable) ───
        $historyQuery = PatientVital::with(['visit.patient', 'visit'])
            ->when($search, fn($q) =>
                $q->whereHas('visit.patient', fn($p) =>
                    $p->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('patient_code', 'like', "%{$search}%")
                )
            )
            ->when($date, fn($q) =>
                $q->whereHas('visit', fn($v) =>
                    $v->whereDate('visit_date', $date)
                )
            )
            ->latest()
            ->paginate(10, ['*'], 'history_page')
            ->withQueryString();

        $history = $historyQuery->getCollection()->map(fn($v) => [
            'id'                 => $v->id,
            'visit_id'           => $v->patient_visit_id,
            'visit_type'         => $v->visit?->visit_type,
            'visit_date'         => $v->visit?->visit_date?->format('M d, Y'),
            'employer'           => $v->visit?->employer_company,
            'patient_name'       => $v->visit?->patient?->full_name ?? '—',
            'patient_code'       => $v->visit?->patient?->patient_code ?? '—',
            'age_sex'            => $v->visit?->patient?->age_sex ?? '—',
            // Key vitals for snapshot display
            'weight_kg'          => $v->weight_kg,
            'bmi'                => $v->bmi,
            'bmi_category'       => $v->bmi_category,
            'temperature_celsius'=> $v->temperature_celsius,
            'pulse_rate'         => $v->pulse_rate,
            'bp'                 => $v->blood_pressure_systolic && $v->blood_pressure_diastolic
                                    ? "{$v->blood_pressure_systolic}/{$v->blood_pressure_diastolic}"
                                    : null,
            'recorded_at'        => $v->updated_at->format('h:i A'),
        ]);
        $historyQuery->setCollection($history);

        return inertia('Nurse/Index', [
            'todayQueue' => $todayQueue,
            'pending'    => $pendingQuery,
            'history'    => $historyQuery,
            'filters'    => ['search' => $search, 'date' => $date],
            'summary'    => [
                'today'      => count($todayQueue),
                'pending'    => PatientVisit::whereDoesntHave('vitals')
                                ->where('status', '!=', 'cancelled')
                                ->whereIn('visit_type', ['opd','pre_employment','follow_up'])
                                ->count(),
                'done_today' => PatientVital::whereHas('visit', fn($q) =>
                                    $q->whereDate('visit_date', today())
                                )->count(),
            ],
        ]);
    }

    public function vitals(PatientVisit $visit)
    {
        $visit->load(['patient', 'vitals']);
        $existing = $visit->vitals;

        return inertia('Nurse/Vitals', [
            'visit' => [
                'id'         => $visit->id,
                'visit_type' => $visit->visit_type,
                'visit_date' => $visit->visit_date->format('M d, Y h:i A'),
                'employer_company' => $visit->employer_company,
            ],
            'patient' => [
                'id'           => $visit->patient->id,
                'full_name'    => $visit->patient->full_name,
                'patient_code' => $visit->patient->patient_code,
                'age_sex'      => $visit->patient->age_sex,
                'age'          => $visit->patient->age,
                'sex'          => $visit->patient->sex,
            ],
            'vitals' => $existing ? [
                'id'                       => $existing->id,
                'weight_kg'                => $existing->weight_kg,
                'height_cm'                => $existing->height_cm,
                'bmi'                      => $existing->bmi,
                'bmi_category'             => $existing->bmi_category,
                'blood_pressure_systolic'  => $existing->blood_pressure_systolic,
                'blood_pressure_diastolic' => $existing->blood_pressure_diastolic,
                'pulse_rate'               => $existing->pulse_rate,
                'respiratory_rate'         => $existing->respiratory_rate,
                'temperature_celsius'      => $existing->temperature_celsius,
                'oxygen_saturation'        => $existing->oxygen_saturation,
                'heart_rate'               => $existing->heart_rate,
                'visual_acuity_right'      => $existing->visual_acuity_right,
                'visual_acuity_left'       => $existing->visual_acuity_left,
                'ishihara_result'          => $existing->ishihara_result,
                'nurse_notes'              => $existing->nurse_notes,
                'recorded_by'             => $existing->recordedBy?->name,
                'recorded_at'             => $existing->updated_at->format('M d, Y h:i A'),
            ] : null,
        ]);
    }

    public function storeVitals(Request $request, PatientVisit $visit)
    {
        $validated = $request->validate([
            'weight_kg'                => ['nullable', 'numeric', 'min:1', 'max:300'],
            'height_cm'                => ['nullable', 'numeric', 'min:50', 'max:250'],
            'blood_pressure_systolic'  => ['nullable', 'integer', 'min:60', 'max:250'],
            'blood_pressure_diastolic' => ['nullable', 'integer', 'min:40', 'max:150'],
            'pulse_rate'               => ['nullable', 'integer', 'min:30', 'max:250'],
            'respiratory_rate'         => ['nullable', 'integer', 'min:5', 'max:60'],
            'temperature_celsius'      => ['nullable', 'numeric', 'min:30', 'max:45'],
            'oxygen_saturation'        => ['nullable', 'integer', 'min:50', 'max:100'],
            'heart_rate'               => ['nullable', 'integer', 'min:30', 'max:250'],
            'visual_acuity_right'      => ['nullable', 'string', 'max:20'],
            'visual_acuity_left'       => ['nullable', 'string', 'max:20'],
            'ishihara_result'          => ['nullable', 'in:Normal,Color Deficiency'],
            'nurse_notes'              => ['nullable', 'string'],
        ]);

        PatientVital::updateOrCreate(
            ['patient_visit_id' => $visit->id],
            [
                ...$validated,
                'patient_id'  => $visit->patient_id,
                'recorded_by' => Auth::id(),
            ]
        );

        return redirect()
            ->route('nurse.index')
            ->with('success', "Vitals recorded for {$visit->patient->full_name}.");
    }
}
