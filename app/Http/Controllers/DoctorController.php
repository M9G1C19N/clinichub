<?php

namespace App\Http\Controllers;

use App\Models\Consultation;
use App\Models\Esignature;
use App\Models\PatientVisit;
use App\Models\PatientVital;
use App\Models\QueueRoomAssignment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Prescription;
class DoctorController extends Controller
{
    // ── DOCTOR DASHBOARD ──────────────────────────

    public function index(Request $request)
    {
            $filter = $request->get('filter', 'all');
            $search = $request->get('search', '');
            $date   = $request->get('date', '');

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

              // ── 4. HISTORY — all finalized consultations ─────
            $historyQuery = Consultation::with(['patient', 'visit'])
                ->where('is_finalized', true)
                ->when($search, fn($q) =>
                    $q->whereHas('patient', fn($p) =>
                        $p->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name',  'like', "%{$search}%")
                        ->orWhere('patient_code', 'like', "%{$search}%")
                    )
                )
                ->when($date, fn($q) =>
                    $q->whereDate('finalized_at', $date)
                )
                ->latest('finalized_at')
                ->paginate(15, ['*'], 'history_page')
                ->withQueryString();

            $history = $historyQuery->getCollection()->map(fn($c) => [
                'id'                => $c->id,
                'visit_id'          => $c->patient_visit_id,
                'patient_name'      => $c->patient->full_name,
                'patient_code'      => $c->patient->patient_code,
                'age_sex'           => $c->patient->age_sex,
                'visit_type'        => $c->visit_type,
                'employer_company'  => $c->employer_company,
                'pe_classification' => $c->pe_classification,
                'pe_class_label'    => $c->pe_classification_label,
                'pe_class_color'    => $c->pe_classification_color,
                'icd10_code'        => $c->icd10_code,
                'icd10_description' => $c->icd10_description,
                'essentially_normal'=> $c->essentially_normal,
                'doctor_notes'      => $c->doctor_notes,
                'finalized_at'      => $c->finalized_at?->format('M d, Y h:i A'),
                'finalized_date'    => $c->finalized_at?->format('M d, Y'),
            ]);
            $historyQuery->setCollection($history);


        // ── SUMMARY COUNTS ────────────────────────────────
            $summary = [
                'today_queue'     => count($todayQueue),
                'pending_total'   => PatientVisit::whereDoesntHave('consultation', fn($q) =>
                                        $q->where('is_finalized', true))
                                    ->where('status', '!=', 'cancelled')
                                    ->whereIn('visit_type', ['opd','pre_employment','annual_pe','exit_pe','follow_up'])
                                    ->count(),
                'pending_pe'      => PatientVisit::whereDoesntHave('consultation', fn($q) =>
                                        $q->where('is_finalized', true))
                                    ->whereIn('visit_type', ['pre_employment','annual_pe','exit_pe'])
                                    ->where('status', '!=', 'cancelled')
                                    ->count(),
                'completed_today' => Consultation::whereDate('finalized_at', today())
                                    ->where('is_finalized', true)->count(),
                'history_total' => Consultation::where('is_finalized', true)->count(),
            ];

        return inertia('Doctor/Index', [
            'todayQueue' => $todayQueue,
            'pending'    => $pendingQuery,
            'completed'  => $completed,
            'history'    => $historyQuery,
            'summary'    => $summary,
            'filter'     => $filter,
            'search'     => $search,
            'date'       => $date,
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
            'labRequest.results.labTest',
            'imagingRequest',
            'drugTestRequest',
            'prescriptions',
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
            'prescriptions' => $visit->prescriptions->map(fn($rx) => [
                'id'            => $rx->id,
                'rx_number'     => $rx->rx_number,
                'items'         => $rx->items,
                'notes'         => $rx->notes,
                'rx_date'       => $rx->rx_date->format('M d, Y'),
                'is_controlled' => $rx->is_controlled,
                'doctor_name'   => $rx->doctor_name,
            ]),
            'patient' => [
                'id'           => $visit->patient->id,
                'full_name'    => $visit->patient->full_name,
                'patient_code' => $visit->patient->patient_code,
                'age_sex'      => $visit->patient->age_sex,
                'age'          => $visit->patient->age,
                'sex'          => $visit->patient->sex,
                'birthdate'    => $visit->patient->birthdate?->format('M d, Y'),
                'address'      => $visit->patient->address,
                'photo_url'    => $visit->patient->photo_path   // ← add this
                        ? asset('storage/' . $visit->patient->photo_path)
                        : null,
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
            'id'                   => $existing->id,
            'chief_complaint'      => $existing->chief_complaint,
            'soap_subjective'      => $existing->soap_subjective,
            'soap_objective'       => $existing->soap_objective,
            'soap_assessment'      => $existing->soap_assessment,
            'soap_plan'            => $existing->soap_plan,
            'icd10_code'           => $existing->icd10_code,
            'icd10_description'    => $existing->icd10_description,
            'diagnosis_type'       => $existing->diagnosis_type,
            'pe_classification'    => $existing->pe_classification,
            'pe_findings'          => $existing->pe_findings,
            'pe_recommendation'    => $existing->pe_recommendation,
            'position_applied'     => $existing->position_applied,
            'requesting_physician' => $existing->requesting_physician,
            // Physical Exam
            'pe_heent'         => $existing->pe_heent,
            'pe_chest_lungs'   => $existing->pe_chest_lungs,
            'pe_heart'         => $existing->pe_heart,
            'pe_abdomen'       => $existing->pe_abdomen,
            'pe_extremities'   => $existing->pe_extremities,
            'pe_neurological'  => $existing->pe_neurological,
            'pe_genitourinary' => $existing->pe_genitourinary,
            'pe_skin'          => $existing->pe_skin,
            'pe_others'        => $existing->pe_others,
            // Medical History
            'past_illnesses'      => $existing->past_illnesses,
            'surgical_history'    => $existing->surgical_history,
            'allergies'           => $existing->allergies,
            'current_medications' => $existing->current_medications,
            'family_history'      => $existing->family_history,
            // Common
            'essentially_normal'    => $existing->essentially_normal,
            'doctor_notes'          => $existing->doctor_notes,
            'follow_up_date'        => $existing->follow_up_date?->format('Y-m-d'),
            'is_finalized'          => $existing->is_finalized,
            // ECG
            'ecg_impression'        => $existing->ecg_impression,
            'ecg_findings'          => $existing->ecg_findings,
            'ecg_noted_by_user_id'  => $existing->ecg_noted_by_user_id,
            'ecg_noted_by_name'     => $existing->ecg_noted_by_name,
            'ecg_noted_by_license'  => $existing->ecg_noted_by_license,
            'ecg_noted_by_signature'=> $existing->ecg_noted_by_signature,
        ] : null,
            'doctor' => [
                'name'       => $doctor->name,
                'prc_number' => $doctor->prc_number ?? '',
                'ptr_number' => $doctor->ptr_number ?? '',
            ],
            // Users with e-signatures for ECG / CXR "Noted by" dropdown
            'signatories' => Esignature::with('user')
                ->where('is_active', true)
                ->get()
                ->map(fn($sig) => [
                    'user_id'        => $sig->user_id,
                    'name'           => $sig->user?->name ?? '',
                    'title'          => $sig->title ?? '',
                    'license_number' => $sig->license_number ?? '',
                    'signature_url'  => $sig->signature_url,
                ]),
        ]);
    }

    // ── STORE / UPDATE CONSULTATION ───────────────

    public function store(Request $request, PatientVisit $visit)
    {
        $isPreEmployment = in_array($visit->visit_type, ['pre_employment', 'annual_pe', 'exit_pe']);

        $rules = [
            'chief_complaint'      => ['nullable', 'string'],
            'soap_subjective'      => ['nullable', 'string'],
            'soap_objective'       => ['nullable', 'string'],
            'soap_assessment'      => ['nullable', 'string'],
            'soap_plan'            => ['nullable', 'string'],
            'icd10_code'           => ['nullable', 'string', 'max:10'],
            'icd10_description'    => ['nullable', 'string', 'max:255'],
            'diagnosis_type'       => ['nullable', 'in:primary,secondary,provisional'],
            'pe_classification'    => ['nullable', 'in:A,B,C,D,E'],
            'pe_findings'          => ['nullable', 'string'],
            'pe_recommendation'    => ['nullable', 'string'],
            'position_applied'     => ['nullable', 'string', 'max:150'],
            'requesting_physician' => ['nullable', 'string', 'max:150'],
            // Physical Exam
            'pe_heent'         => ['nullable', 'string'],
            'pe_chest_lungs'   => ['nullable', 'string'],
            'pe_heart'         => ['nullable', 'string'],
            'pe_abdomen'       => ['nullable', 'string'],
            'pe_extremities'   => ['nullable', 'string'],
            'pe_neurological'  => ['nullable', 'string'],
            'pe_genitourinary' => ['nullable', 'string'],
            'pe_skin'          => ['nullable', 'string'],
            'pe_others'        => ['nullable', 'string'],
            // Medical History
            'past_illnesses'      => ['nullable', 'string'],
            'surgical_history'    => ['nullable', 'string'],
            'allergies'           => ['nullable', 'string'],
            'current_medications' => ['nullable', 'string'],
            'family_history'      => ['nullable', 'string'],
            'doctor_notes'           => ['nullable', 'string'],
            'is_finalized'           => ['boolean'],
            'essentially_normal'     => ['boolean'],
            'follow_up_date'         => ['nullable', 'date'],
            // ECG
            'ecg_impression'         => ['nullable', 'string'],
            'ecg_findings'           => ['nullable', 'string'],
            'ecg_noted_by_user_id'   => ['nullable', 'integer', 'exists:users,id'],
        ];

        $validated = $request->validate($rules);

        // Resolve ECG signatory details for denormalized storage
        $ecgExtra = [];
        if (!empty($validated['ecg_noted_by_user_id'])) {
            $ecgSig = Esignature::with('user')
                ->where('user_id', $validated['ecg_noted_by_user_id'])
                ->where('is_active', true)
                ->first();
            if ($ecgSig) {
                $ecgExtra = [
                    'ecg_noted_by_name'      => $ecgSig->user?->name,
                    'ecg_noted_by_license'   => $ecgSig->license_number,
                    'ecg_noted_by_signature' => $ecgSig->signature_path,
                ];
            }
        }

        $consultation = Consultation::updateOrCreate(
            ['patient_visit_id' => $visit->id],
            [
                ...$validated,
                ...$ecgExtra,
                'patient_id'       => $visit->patient_id,
                'visit_type'       => $visit->visit_type,
                'employer_company' => $visit->employer_company,
                'doctor_id'        => Auth::id(),
                'finalized_at'     => $validated['is_finalized'] ? now() : null,
                'soap_assessment'  => $validated['essentially_normal']
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

    // ── PRINT MEDICAL EXAM REPORT ─────────────────────
    public function printExam(PatientVisit $visit)
    {
        $visit->load([
            'patient',
            'vitals',
            'consultation.doctor.esignature',
            'labRequest.results.labTest',
            'imagingRequest',
            'drugTestRequest',
        ]);

        $consultation = $visit->consultation;
        $patient      = $visit->patient;
        $vitals       = $visit->vitals;
        $labRequest   = $visit->labRequest;
        $imaging      = $visit->imagingRequest;
        $drugTest     = $visit->drugTestRequest;

        // Map lab results by test code for easy access in template
        $labResultMap = [];
        if ($labRequest?->results) {
            foreach ($labRequest->results as $r) {
                $labResultMap[$r->labTest?->test_code] = [
                    'value'   => $r->result_value,
                    'unit'    => $r->unit,
                    'flag'    => $r->abnormal_flag,
                    'is_abnormal' => $r->is_abnormal,
                    'normal_range' => $r->normal_range_display,
                ];
            }
        }

        return inertia('Doctor/Print', [
            'visit' => [
                'id'               => $visit->id,
                'case_number'      => $visit->case_number,
                'visit_type'       => $visit->visit_type,
                'visit_date'       => $visit->visit_date->format('M d, Y'),
                'employer_company' => $visit->employer_company,
            ],
            'patient' => [
                'full_name'    => $patient->full_name,
                'last_name'    => $patient->last_name,
                'first_name'   => $patient->first_name,
                'middle_name'  => $patient->middle_name ?? '',
                'age'          => $patient->age,
                'sex'          => $patient->sex,
                'age_sex'      => $patient->age_sex,
                'birthdate'    => $patient->birthdate?->format('m/d/Y'),
                'civil_status' => $patient->civil_status ?? '',
                'address'      => $patient->address ?? '',
                'patient_code' => $patient->patient_code,
                 'photo_path'               => $patient->photo_path
                    ? asset('storage/' . $patient->photo_path)
                    : null,
            ],
            'vitals' => $vitals ? [
                'weight_kg'                => $vitals->weight_kg,
                'height_cm'                => $vitals->height_cm,
                'bmi'                      => $vitals->bmi,
                'blood_pressure_systolic'  => $vitals->blood_pressure_systolic,
                'blood_pressure_diastolic' => $vitals->blood_pressure_diastolic,
                'pulse_rate'               => $vitals->pulse_rate,
                'temperature_celsius'      => $vitals->temperature_celsius,
                'visual_acuity_right'      => $vitals->visual_acuity_right,
                'visual_acuity_left'       => $vitals->visual_acuity_left,
                'ishihara_result'          => $vitals->ishihara_result,
                'past_illnesses_flags'    => $vitals->past_illnesses_flags ?? [],
                'past_illnesses_remarks'  => $vitals->past_illnesses_remarks,
                'present_symptoms'        => $vitals->present_symptoms,
                'family_history'          => $vitals->family_history,
                'accidents_injuries'      => $vitals->accidents_injuries,
                'surgical_history_detail' => $vitals->surgical_history_detail,
                'allergies_flags'         => $vitals->allergies_flags ?? [],
                'allergies_others'        => $vitals->allergies_others,
                'menstrual_cycle'         => $vitals->menstrual_cycle,
                'lmp'                     => $vitals->lmp,
                'ob_gravida'              => $vitals->ob_gravida,
                'ob_para'                 => $vitals->ob_para,
                'ob_nulligravida'         => $vitals->ob_nulligravida,
                'tobacco_use'             => $vitals->tobacco_use,
                'alcohol_use'             => $vitals->alcohol_use,
                'conversational_hearing'  => $vitals->conversational_hearing,
                'visual_acuity_near_right'=> $vitals->visual_acuity_near_right,
                'visual_acuity_near_left' => $vitals->visual_acuity_near_left,
                'color_vision_result'     => $vitals->color_vision_result,
                'pe_findings_normal'      => $vitals->pe_findings_normal ?? [],
                'pe_findings_remarks'     => $vitals->pe_findings_remarks,
            ] : null,
            'consultation' => $consultation ? [
                'pe_classification'    => $consultation->pe_classification,
                'pe_findings'          => $consultation->pe_findings,
                'pe_recommendation'    => $consultation->pe_recommendation,
                'position_applied'     => $consultation->position_applied,
                'requesting_physician' => $consultation->requesting_physician,
                'essentially_normal'   => $consultation->essentially_normal,
                'pe_heent'             => $consultation->pe_heent,
                'pe_chest_lungs'       => $consultation->pe_chest_lungs,
                'pe_heart'             => $consultation->pe_heart,
                'pe_abdomen'           => $consultation->pe_abdomen,
                'pe_extremities'       => $consultation->pe_extremities,
                'pe_neurological'      => $consultation->pe_neurological,
                'pe_genitourinary'     => $consultation->pe_genitourinary,
                'pe_skin'              => $consultation->pe_skin,
                'pe_others'            => $consultation->pe_others,
                'past_illnesses'       => $consultation->past_illnesses,
                'surgical_history'     => $consultation->surgical_history,
                'allergies'            => $consultation->allergies,
                'current_medications'  => $consultation->current_medications,
                'family_history'       => $consultation->family_history,
                'doctor_notes'         => $consultation->doctor_notes,
                'finalized_at'         => $consultation->finalized_at?->format('M d, Y'),
                'doctor_name'            => $consultation->doctor?->name ?? Auth::user()->name,
                'doctor_prc'             => $consultation->doctor?->prc_number ?? '',
                'doctor_ptr'             => $consultation->doctor?->ptr_number ?? '',
                'doctor_signature'       => $this->sigUrl($consultation->doctor?->esignature?->signature_path),
                // ECG
                'ecg_impression'         => $consultation->ecg_impression,
                'ecg_findings'           => $consultation->ecg_findings,
                'ecg_noted_by_name'      => $consultation->ecg_noted_by_name,
                'ecg_noted_by_license'   => $consultation->ecg_noted_by_license,
                'ecg_noted_by_signature' => $this->sigUrl($consultation->ecg_noted_by_signature),
            ] : null,
            'labResults' => $labResultMap,
            'labRequest' => $labRequest ? [
                'status'               => $labRequest->status,
                'examined_by_name'     => $labRequest->examined_by_name,
                'examined_by_license'  => $labRequest->examined_by_license,
                'examined_by_signature'=> $this->sigUrl($labRequest->examined_by_signature),
                'noted_by_name'        => $labRequest->noted_by_name,
                'noted_by_license'     => $labRequest->noted_by_license,
                'noted_by_signature'   => $this->sigUrl($labRequest->noted_by_signature),
            ] : null,
            // CXR fields remapped to match what the print template expects
            'imaging' => $imaging ? [
                'request_number'          => $imaging->request_number,
                'imaging_type_label'      => $imaging->imaging_type_label,
                'radiographic_findings'   => $imaging->radiographic_findings,
                'impression'              => $imaging->impression,
                'is_provisional'          => $imaging->is_provisional,
                'rad_tech_name'           => $imaging->rad_tech_name,
                'radiologist_name'        => $imaging->radiologist_name,
                'radiologist_license'     => $imaging->radiologist_license,
                'radiologist_signature'   => $imaging->radiologist_signature
                    ? $this->sigUrl($imaging->radiologist_signature)
                    : null,
            ] : null,
            'drugTest' => $drugTest ? [
                'result'      => $drugTest->result,
                'drugs_label' => $drugTest->drugs_label,
                'code_number' => $drugTest->code_number,
            ] : null,
        ]);
    }
    public function storePrescription(Request $request, PatientVisit $visit)
    {
        $validated = $request->validate([
            'items'          => ['required', 'array', 'min:1'],
            'items.*.drug'   => ['required', 'string', 'max:150'],
            'items.*.dosage' => ['nullable', 'string', 'max:100'],
            'items.*.form'   => ['nullable', 'string', 'max:50'],
            'items.*.quantity'    => ['nullable', 'string', 'max:50'],
            'items.*.frequency'   => ['nullable', 'string', 'max:100'],
            'items.*.duration'    => ['nullable', 'string', 'max:100'],
            'items.*.instructions'=> ['nullable', 'string'],
            'notes'          => ['nullable', 'string'],
            'is_controlled'  => ['boolean'],
        ]);

        $doctor = Auth::user();

        Prescription::create([
            'patient_id'          => $visit->patient_id,
            'patient_visit_id'    => $visit->id,
            'doctor_id'           => $doctor->id,
            'items'               => $validated['items'],
            'notes'               => $validated['notes'] ?? null,
            'is_controlled'       => $validated['is_controlled'] ?? false,
            'rx_date'             => now()->toDateString(),
            'doctor_name'         => $doctor->name,
            'doctor_prc'          => $doctor->prc_number,
            'doctor_ptr'          => $doctor->ptr_number,
            'doctor_s2'           => $doctor->s2_number,
            'doctor_specialization' => $doctor->specialization,
            'patient_name'        => $visit->patient->full_name,
            'patient_age_sex'     => $visit->patient->age_sex,
            'patient_address'     => $visit->patient->address,
        ]);

        return back()->with('success', 'Prescription saved.');
    }

public function destroyPrescription(Prescription $prescription)
{
    $prescription->delete();
    return back()->with('success', 'Prescription deleted.');
}

public function printPrescription(Prescription $prescription)
{
    return inertia('Doctor/PrintPrescription', [
        'prescription' => [
            'id'             => $prescription->id,
            'rx_number'      => $prescription->rx_number,
            'rx_date'        => $prescription->rx_date->format('M d, Y'),
            'items'          => $prescription->items,
            'notes'          => $prescription->notes,
            'is_controlled'  => $prescription->is_controlled,
            'doctor_name'    => $prescription->doctor_name,
            'doctor_prc'     => $prescription->doctor_prc,
            'doctor_ptr'     => $prescription->doctor_ptr,
            'doctor_s2'      => $prescription->doctor_s2,
            'doctor_specialization' => $prescription->doctor_specialization,
        ],
        'patient' => [
            'full_name'    => $prescription->patient_name,
            'age_sex'      => $prescription->patient_age_sex,
            'address'      => $prescription->patient_address,
            'patient_code' => $prescription->patient->patient_code,
        ],
    ]);
}

private function sigUrl(?string $val): ?string
{
    if (!$val) return null;
    if (str_starts_with($val, 'http')) {
        if (preg_match('#/storage/(.+)$#', $val, $m)) {
            return asset('storage/' . $m[1]);
        }
        return $val;
    }
    return asset('storage/' . $val);
}
}
