<?php

namespace App\Http\Controllers;

use App\Models\Consultation;
use App\Models\PatientVisit;
use App\Models\PatientVital;
use App\Models\QueueRoomAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DoctorController extends Controller
{
    // ── DOCTOR DASHBOARD ──────────────────────────

    public function index(Request $request)
    {
        $filter = $request->get('filter', 'all');

        // ── 1. TODAY'S QUEUE — physically in clinic ───────
        $todayQueue = QueueRoomAssignment::with([
            'ticket.patient',
            'ticket.visit.vitals',
            'ticket.visit.consultation',
        ])
        ->today()
        ->forRoom('interview_room')
        ->whereIn('status', ['waiting', 'calling', 'serving'])
        ->orderByRaw("FIELD(status, 'serving', 'calling', 'waiting')")
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
                'has_consultation' => $a->ticket->visit?->consultation !== null,
                'is_finalized'     => $a->ticket->visit?->consultation?->is_finalized ?? false,
            ] : null,
        ]);

        // ── 2. PENDING DIAGNOSIS — any date, results in ───
        // Patients who have a visit but NO finalized consultation
        // These can be from any day — doctor works on them anytime
        $pendingQuery = PatientVisit::with([
            'patient',
            'vitals',
            'consultation',
            'invoice.items',
        ])
        ->whereDoesntHave('consultation', fn($q) =>
            $q->where('is_finalized', true)
        )
        ->where('status', '!=', 'cancelled')
        ->whereIn('visit_type', ['opd', 'pre_employment', 'annual_pe', 'exit_pe', 'follow_up'])
        ->when($filter === 'pre_employment', fn($q) =>
            $q->where('visit_type', 'pre_employment')
        )
        ->when($filter === 'opd', fn($q) =>
            $q->where('visit_type', 'opd')
        )
        ->orderByDesc('visit_date')
        ->paginate(15)
        ->withQueryString();

        $pending = $pendingQuery->getCollection()->map(fn($v) => [
            'id'               => $v->id,
            'visit_type'       => $v->visit_type,
            'visit_date'       => $v->visit_date->format('M d, Y'),
            'visit_time'       => $v->visit_date->format('h:i A'),
            'employer_company' => $v->employer_company,
            'status'           => $v->status,
            'has_vitals'       => $v->vitals !== null,
            'has_draft'        => $v->consultation !== null,
            'services_count'   => count($v->services_selected ?? []),
            'services'         => collect($v->invoice?->items ?? [])->map(fn($i) => [
                'code' => $i->service_code,
                'name' => $i->service_name,
            ])->toArray(),
            'patient' => [
                'id'           => $v->patient->id,
                'full_name'    => $v->patient->full_name,
                'patient_code' => $v->patient->patient_code,
                'age_sex'      => $v->patient->age_sex,
            ],
        ]);
        $pendingQuery->setCollection($pending);

        // ── 3. COMPLETED TODAY ────────────────────────────
        $completed = Consultation::with(['patient', 'visit'])
            ->whereDate('finalized_at', today())
            ->where('is_finalized', true)
            ->latest('finalized_at')
            ->get()
            ->map(fn($c) => [
                'id'                => $c->id,
                'patient_name'      => $c->patient->full_name,
                'patient_code'      => $c->patient->patient_code,
                'visit_type'        => $c->visit_type,
                'pe_classification' => $c->pe_classification,
                'icd10_code'        => $c->icd10_code,
                'icd10_description' => $c->icd10_description,
                'is_finalized'      => $c->is_finalized,
                'finalized_at'      => $c->finalized_at?->format('h:i A'),
                'visit_id'          => $c->patient_visit_id,
            ]);

        // ── SUMMARY COUNTS ────────────────────────────────
        $summary = [
            'today_queue'    => count($todayQueue),
            'pending_total'  => PatientVisit::whereDoesntHave('consultation', fn($q) =>
                                    $q->where('is_finalized', true)
                                )->where('status', '!=', 'cancelled')
                                ->whereIn('visit_type', ['opd', 'pre_employment', 'annual_pe', 'exit_pe', 'follow_up'])
                                ->count(),
            'pending_pe'     => PatientVisit::whereDoesntHave('consultation', fn($q) =>
                                    $q->where('is_finalized', true)
                                )->where('visit_type', 'pre_employment')
                                ->where('status', '!=', 'cancelled')
                                ->count(),
            'completed_today'=> Consultation::whereDate('finalized_at', today())
                                ->where('is_finalized', true)->count(),
        ];

        return inertia('Doctor/Index', [
            'todayQueue' => $todayQueue,
            'pending'    => $pendingQuery,
            'completed'  => $completed,
            'summary'    => $summary,
            'filter'     => $filter,
        ]);
    }

    // ── CONSULTATION FORM ─────────────────────────

    public function consult(PatientVisit $visit)
    {
        $doctor = Auth::user();

        $visit->load([
            'patient',
            'vitals',
            'consultation',
            'invoice.items',
            'labRequest.results.labTest',    // ← lab results
            'imagingRequest',                // ← xray findings
            'drugTestRequest',               // ← drug test result
        ]);

        $existing = $visit->consultation;

        $services = $visit->invoice?->items->map(fn($i) => [
            'service_code' => $i->service_code,
            'service_name' => $i->service_name,
        ])->toArray() ?? [];

        // ── Lab Results ─────────────────────────────
        $labRequest = $visit->labRequest;
        $labResults = null;
        if ($labRequest) {
            $resultsByCode = $labRequest->results->keyBy(fn($r) => $r->labTest?->test_code);
            $labResults = [
                'request_number'      => $labRequest->request_number,
                'status'              => $labRequest->status,
                'is_released'         => $labRequest->status === 'released',
                'examined_by_name'    => $labRequest->examined_by_name,
                'noted_by_name'       => $labRequest->noted_by_name,
                'has_abnormal'        => $labRequest->results->where('is_abnormal', true)->count() > 0,
                'abnormal_count'      => $labRequest->results->where('is_abnormal', true)->count(),
                'results'             => $labRequest->results->map(fn($r) => [
                    'test_code'      => $r->labTest?->test_code,
                    'test_name'      => $r->labTest?->test_name,
                    'category'       => $r->labTest?->category,
                    'result_value'   => $r->result_value,
                    'unit'           => $r->unit,
                    'normal_range'   => $r->normal_range_display,
                    'is_abnormal'    => $r->is_abnormal,
                    'abnormal_flag'  => $r->abnormal_flag,
                    'remarks'        => $r->remarks,
                ])->sortBy(fn($r) => [
                    // Sort by category group for clean display
                    match($r['category']) {
                        'hematology' => 1,
                        'chemistry'  => 2,
                        'urinalysis' => 3,
                        'stool'      => 4,
                        'serology'   => 5,
                        default      => 6
                    },
                ])->values()->toArray(),
            ];
        }

        // ── Imaging / XRay Results ───────────────────
        $imaging = $visit->imagingRequest;
        $imagingResult = $imaging ? [
            'request_number'        => $imaging->request_number,
            'imaging_type'          => $imaging->imaging_type,
            'imaging_type_label'    => $imaging->imaging_type_label,
            'radiographic_findings' => $imaging->radiographic_findings,
            'impression'            => $imaging->impression,
            'is_provisional'        => $imaging->is_provisional,
            'status'                => $imaging->status,
            'is_released'           => $imaging->status === 'released',
            'rad_tech_name'         => $imaging->rad_tech_name,
            'radiologist_name'      => $imaging->radiologist_name,
        ] : null;

        // ── Drug Test Result ─────────────────────────
        $drugTest = $visit->drugTestRequest;
        $drugTestResult = $drugTest ? [
            'code_number'  => $drugTest->code_number,
            'result'       => $drugTest->result,
            'drugs_label'  => $drugTest->drugs_label,
            'status'       => $drugTest->status,
            'is_released'  => $drugTest->status === 'released',
            'purpose'      => $drugTest->test_purpose_label,
        ] : null;

        return inertia('Doctor/Consult', [
            'visit' => [
                'id'               => $visit->id,
                'visit_type'       => $visit->visit_type,
                'visit_date'       => $visit->visit_date->format('M d, Y h:i A'),
                'employer_company' => $visit->employer_company,
                'chief_complaint'  => $visit->chief_complaint,
                'services'         => $services,
            ],
            'patient' => [
                'id'           => $visit->patient->id,
                'full_name'    => $visit->patient->full_name,
                'patient_code' => $visit->patient->patient_code,
                'age_sex'      => $visit->patient->age_sex,
                'age'          => $visit->patient->age,
                'sex'          => $visit->patient->sex,
                'birthdate'    => $visit->patient->birthdate?->format('M d, Y'),
                'address'      => $visit->patient->address,
            ],
            'vitals' => $visit->vitals ? [
                'weight_kg'                => $visit->vitals->weight_kg,
                'height_cm'                => $visit->vitals->height_cm,
                'bmi'                      => $visit->vitals->bmi,
                'bmi_category'             => $visit->vitals->bmi_category,
                'blood_pressure_systolic'  => $visit->vitals->blood_pressure_systolic,
                'blood_pressure_diastolic' => $visit->vitals->blood_pressure_diastolic,
                'pulse_rate'               => $visit->vitals->pulse_rate,
                'temperature_celsius'      => $visit->vitals->temperature_celsius,
                'oxygen_saturation'        => $visit->vitals->oxygen_saturation,
                'visual_acuity_right'      => $visit->vitals->visual_acuity_right,
                'visual_acuity_left'       => $visit->vitals->visual_acuity_left,
                'ishihara_result'          => $visit->vitals->ishihara_result,
                'nurse_notes'              => $visit->vitals->nurse_notes,
            ] : null,
            'labResults'     => $labResults,
            'imagingResult'  => $imagingResult,
            'drugTestResult' => $drugTestResult,
            'consultation' => $existing ? [
                'id'                 => $existing->id,
                'chief_complaint'    => $existing->chief_complaint,
                'soap_subjective'    => $existing->soap_subjective,
                'soap_objective'     => $existing->soap_objective,
                'soap_assessment'    => $existing->soap_assessment,
                'soap_plan'          => $existing->soap_plan,
                'icd10_code'         => $existing->icd10_code,
                'icd10_description'  => $existing->icd10_description,
                'diagnosis_type'     => $existing->diagnosis_type,
                'pe_classification'  => $existing->pe_classification,
                'pe_findings'        => $existing->pe_findings,
                'pe_recommendation'  => $existing->pe_recommendation,
                'essentially_normal' => $existing->essentially_normal,
                'doctor_notes'       => $existing->doctor_notes,
                'follow_up_date'     => $existing->follow_up_date?->format('Y-m-d'),
                'is_finalized'       => $existing->is_finalized,
            ] : null,
            'doctor' => [
                'name'       => $doctor->name,
                'prc_number' => $doctor->prc_number ?? '',
                'ptr_number' => $doctor->ptr_number ?? '',
            ],
        ]);
    }

    // ── STORE / UPDATE CONSULTATION ───────────────

    public function store(Request $request, PatientVisit $visit)
    {
        $isPreEmployment = in_array($visit->visit_type, ['pre_employment', 'annual_pe', 'exit_pe']);

        $rules = [
            'chief_complaint'   => ['nullable', 'string'],
            'soap_subjective'   => ['nullable', 'string'],
            'soap_objective'    => ['nullable', 'string'],
            'soap_assessment'   => ['nullable', 'string'],
            'soap_plan'         => ['nullable', 'string'],
            'icd10_code'        => ['nullable', 'string', 'max:10'],
            'icd10_description' => ['nullable', 'string', 'max:255'],
            'diagnosis_type'    => ['nullable', 'in:primary,secondary,provisional'],
            'pe_classification' => ['nullable', 'in:A,B,C,D,E'],
            'pe_findings'       => ['nullable', 'string'],
            'pe_recommendation' => ['nullable', 'string'],
            'doctor_notes'      => ['nullable', 'string'],
            'is_finalized'      => ['boolean'],
            'essentially_normal' => ['boolean'],
            'follow_up_date'     => ['nullable', 'date'],
        ];

        $validated = $request->validate($rules);

        $consultation = Consultation::updateOrCreate(
            ['patient_visit_id' => $visit->id],
            [
                ...$validated,
                'patient_id'       => $visit->patient_id,
                'visit_type'       => $visit->visit_type,
                'employer_company' => $visit->employer_company,
                'doctor_id'        => Auth::id(),
                'finalized_at'     => $validated['is_finalized'] ? now() : null,
                'soap_assessment'   => $validated['essentially_normal']
                ? '***ESSENTIALLY NORMAL FINDINGS***'
                : ($validated['soap_assessment'] ?? null),
            ]
        );

        // Mark visit as completed if finalized
        if ($validated['is_finalized']) {
            $visit->update(['status' => 'completed']);
        }

        $action = $validated['is_finalized'] ? 'finalized' : 'saved';

        return redirect()
            ->route('doctor.index')
            ->with('success', "Consultation {$action} for {$visit->patient->full_name}.");
    }
}
