<?php

namespace App\Http\Controllers;

use App\Models\LabTest;
use App\Models\LaboratoryRequest;
use App\Models\LaboratoryResult;
use App\Models\PatientVisit;
use App\Models\QueueRoomAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LaboratoryController extends Controller
{
    // ── LAB DASHBOARD ─────────────────────────────────

    public function index()
    {
        // Today's lab queue from room assignments
        $queue = QueueRoomAssignment::with([
            'ticket.patient',
            'ticket.visit.labRequest',
            'ticket.visit.invoice.items',
        ])
        ->today()
        ->forRoom('laboratory')
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
                'sex'          => $a->ticket?->patient?->sex ?? 'male',
            ],
            'visit' => $a->ticket?->patient_visit_id ? [
                'id'               => $a->ticket->visit?->id,
                'visit_type'       => $a->ticket->visit?->visit_type,
                'employer_company' => $a->ticket->visit?->employer_company,
                'has_results'      => $a->ticket->visit?->labRequest !== null,
                'is_released'      => $a->ticket->visit?->labRequest?->status === 'released',
                'services'         => collect($a->ticket->visit?->invoice?->items ?? [])
                    ->filter(fn($i) => in_array($i->service_code, [
                        'CBC','UA','FECALYSIS','BLOODTYPING','FBS','RBS','BUN',
                        'CREATININE','URICACID','CHOLESTEROL','TRIGLYCERIDES',
                        'HDLLDL','SGOT','SGPT','HBSAG','VDRL','PREGNANCY','DENGUE','THYROID'
                    ]))
                    ->map(fn($i) => ['code' => $i->service_code, 'name' => $i->service_name])
                    ->values()->toArray(),
            ] : null,
        ]);

        // Pending results — released today
        $released = LaboratoryRequest::with(['patient', 'visit'])
            ->whereDate('released_at', today())
            ->where('status', 'released')
            ->latest('released_at')
            ->get()
            ->map(fn($r) => [
                'id'             => $r->id,
                'request_number' => $r->request_number,
                'patient_name'   => $r->patient->full_name,
                'patient_code'   => $r->patient->patient_code,
                'released_at'    => $r->released_at->format('h:i A'),
                'visit_id'       => $r->patient_visit_id,
            ]);

        return inertia('Laboratory/Index', [
            'queue'    => $queue,
            'released' => $released,
            'summary'  => [
                'waiting'  => collect($queue)->where('status', 'waiting')->count(),
                'serving'  => collect($queue)->where('status', 'serving')->count(),
                'released' => count($released),
            ],
        ]);
    }

    // ── RESULT ENTRY FORM ──────────────────────────────

    public function enter(PatientVisit $visit)
    {
        $currentUser = Auth::user();

        $visit->load([
            'patient',
            'invoice.items',
            'labRequest.results.labTest',
        ]);

        // Determine which lab tests are needed
        // Map service codes to lab test categories
        $serviceToCategories = [
            'CBC'          => ['hematology'],
            'UA'           => ['urinalysis'],
            'FECALYSIS'    => ['stool'],
            'BLOODTYPING'  => ['hematology'],
            'FBS'          => ['chemistry'],
            'RBS'          => ['chemistry'],
            'BUN'          => ['chemistry'],
            'CREATININE'   => ['chemistry'],
            'URICACID'     => ['chemistry'],
            'CHOLESTEROL'  => ['chemistry'],
            'TRIGLYCERIDES'=> ['chemistry'],
            'HDLLDL'       => ['chemistry'],
            'SGOT'         => ['chemistry'],
            'SGPT'         => ['chemistry'],
            'HBSAG'        => ['serology'],
            'VDRL'         => ['serology'],
            'PREGNANCY'    => ['serology'],
            'DENGUE'       => ['serology'],
            'THYROID'      => ['thyroid'],
            'PSA'          => ['serology'],
            'BLOODTYPING'  => ['hematology', 'serology'],
        ];

        // Get ordered lab services from invoice
        $orderedServices = collect($visit->invoice?->items ?? [])
            ->pluck('service_code')
            ->toArray();

        // Collect needed categories
        $neededCategories = [];
        foreach ($orderedServices as $code) {
            $cats = $serviceToCategories[$code] ?? [];
            foreach ($cats as $cat) {
                if (!in_array($cat, $neededCategories)) {
                    $neededCategories[] = $cat;
                }
            }
        }
        if (in_array('PSA', $orderedServices)) $specificTestCodes[] = 'PSA';
        // Get tests for needed categories + specific codes
        $specificTestCodes = [];
        if (in_array('BLOODTYPING', $orderedServices)) $specificTestCodes[] = 'BTYPE';
        if (in_array('HBSAG',       $orderedServices)) $specificTestCodes[] = 'HBSAG';
        if (in_array('VDRL',        $orderedServices)) $specificTestCodes[] = 'VDRL';
        if (in_array('PREGNANCY',   $orderedServices)) $specificTestCodes[] = 'PREG';

        $tests = LabTest::active()
            ->where(function($q) use ($neededCategories, $specificTestCodes) {
                $q->whereIn('category', $neededCategories);
                if (!empty($specificTestCodes)) {
                    $q->orWhereIn('test_code', $specificTestCodes);
                }
            })
            ->orderBy('sort_order')
            ->get();

        // Get or create lab request
        $labRequest = $visit->labRequest;

        // Map existing results by test_id for prefill
        $existingResults = $labRequest
            ? $labRequest->results->keyBy('lab_test_id')
            : collect();

        $patient = $visit->patient;

        // Build test data with normal ranges and existing results
        $testData = $tests->map(fn($t) => [
            'id'                 => $t->id,
            'test_code'          => $t->test_code,
            'test_name'          => $t->test_name,
            'category'           => $t->category,
            'unit'               => $t->unit,
            'normal_range'       => $t->getNormalRangeForPatient($patient->sex),
            'is_text_result'     => $t->is_text_result,
            // Existing result
            'result_value'       => $existingResults[$t->id]?->result_value ?? '',
            'remarks'            => $existingResults[$t->id]?->remarks ?? '',
            'is_abnormal'        => $existingResults[$t->id]?->is_abnormal ?? false,
            'abnormal_flag'      => $existingResults[$t->id]?->abnormal_flag ?? null,
        ]);

        $allTests = LabTest::active()
        ->orderBy('sort_order')
        ->get()
        ->map(fn($t) => [
            'id'             => $t->id,
            'test_code'      => $t->test_code,
            'test_name'      => $t->test_name,
            'category'       => $t->category,
            'unit'           => $t->unit,
            'normal_range'   => $t->getNormalRangeForPatient($patient->sex),
            'is_text_result' => $t->is_text_result,
            // Pull result if it exists
            'result_value'   => $existingResults[$t->id]?->result_value ?? '',
            'remarks'        => $existingResults[$t->id]?->remarks ?? '',
            'is_abnormal'    => $existingResults[$t->id]?->is_abnormal ?? false,
            'abnormal_flag'  => $existingResults[$t->id]?->abnormal_flag ?? null,
        ]);

        return inertia('Laboratory/Enter', [
            'visit' => [
                'id'               => $visit->id,
                'visit_type'       => $visit->visit_type,
                'visit_date'       => $visit->visit_date->format('M d, Y'),
                'employer_company' => $visit->employer_company,
                'services'         => $orderedServices,
            ],
            'patient' => [
                'id'           => $patient->id,
                'full_name'    => $patient->full_name,
                'patient_code' => $patient->patient_code,
                'age_sex'      => $patient->age_sex,
                'sex'          => $patient->sex,
                'birthdate'    => $patient->birthdate?->format('M d, Y'),
            ],
            'labRequest' => $labRequest ? [
                'id'                  => $labRequest->id,
                'request_number'      => $labRequest->request_number,
                'status'              => $labRequest->status,
                'examined_by_name'    => $labRequest->examined_by_name,
                'examined_by_license' => $labRequest->examined_by_license,
                'noted_by_name'       => $labRequest->noted_by_name,
                'noted_by_license'    => $labRequest->noted_by_license,
            ] : null,
            'tests'  => $testData,
        'currentUser' => [
                    'name'       => $currentUser->name,
                    'prc_number' => $currentUser->prc_number ?? '',
            ],
            'tests'   => $testData,
            'allTests'  => $allTests,
        ]);
    }

    // ── SAVE RESULTS ───────────────────────────────────

    public function saveResults(Request $request, PatientVisit $visit)
    {
        $validated = $request->validate([
            'results'              => ['required', 'array'],
            'results.*.test_id'    => ['required', 'exists:lab_tests,id'],
            'results.*.value'      => ['nullable', 'string', 'max:100'],
            'results.*.remarks'    => ['nullable', 'string'],
            'examined_by_name'     => ['nullable', 'string', 'max:100'],
            'examined_by_license'  => ['nullable', 'string', 'max:50'],
            'noted_by_name'        => ['nullable', 'string', 'max:100'],
            'noted_by_license'     => ['nullable', 'string', 'max:50'],
            'general_remarks'       => ['nullable', 'string'],
            'release'              => ['boolean'],
        ]);

        DB::transaction(function () use ($validated, $visit) {

            // Create or get lab request
            $labRequest = LaboratoryRequest::updateOrCreate(
                ['patient_visit_id' => $visit->id],
                [
                    'patient_id'          => $visit->patient_id,
                    'requested_by'        => Auth::id(),
                    'request_date'        => today(),
                    'examined_by_name'    => $validated['examined_by_name'] ?? null,
                    'examined_by_license' => $validated['examined_by_license'] ?? null,
                    'noted_by_name'       => $validated['noted_by_name'] ?? null,
                    'noted_by_license'    => $validated['noted_by_license'] ?? null,
                    'status'              => $validated['release'] ? 'released' : 'processing',
                    'released_at'         => $validated['release'] ? now() : null,
                    'released_by'         => $validated['release'] ? Auth::id() : null,
                    'clinical_notes'      => $validated['general_remarks'] ?? null,
                ]
            );

            // Save each result row
            foreach ($validated['results'] as $row) {
                if ($row['value'] === null || $row['value'] === '') continue;

                $test = LabTest::find($row['test_id']);

                // Auto-determine abnormal flag
                $isAbnormal  = false;
                $abnormalFlag = null;

                if (!$test->is_text_result && is_numeric($row['value'])) {
                    $val = floatval($row['value']);
                    if ($test->normal_min && $val < $test->normal_min) {
                        $isAbnormal = true; $abnormalFlag = 'L';
                    } elseif ($test->normal_max && $val > $test->normal_max) {
                        $isAbnormal = true; $abnormalFlag = 'H';
                    }
                }

                LaboratoryResult::updateOrCreate(
                    [
                        'lab_request_id' => $labRequest->id,
                        'lab_test_id'    => $row['test_id'],
                    ],
                    [
                        'result_value'        => $row['value'],
                        'unit'                => $test->unit,
                        'normal_range_display'=> $test->getNormalRangeForPatient($visit->patient->sex),
                        'is_abnormal'         => $isAbnormal,
                        'abnormal_flag'       => $abnormalFlag,
                        'remarks'             => $row['remarks'] ?? null,
                        'performed_by'        => Auth::id(),
                    ]
                );
            }
        });

        $action = $validated['release'] ? 'released' : 'saved';

        return redirect()
            ->route('laboratory.index')
            ->with('success', "Lab results {$action} for {$visit->patient->full_name}.");
    }

    public function print(PatientVisit $visit)
    {
        $visit->load(['patient', 'invoice.items', 'labRequest.results.labTest']);

        $patient   = $visit->patient;
        $labRequest = $visit->labRequest;

        $existingResults = $labRequest
            ? $labRequest->results->keyBy('lab_test_id')
            : collect();

        $allTests = LabTest::active()
            ->orderBy('sort_order')
            ->get()
            ->map(fn($t) => [
                'id'             => $t->id,
                'test_code'      => $t->test_code,
                'test_name'      => $t->test_name,
                'category'       => $t->category,
                'unit'           => $t->unit,
                'normal_range'   => $t->getNormalRangeForPatient($patient->sex),
                'is_text_result' => $t->is_text_result,
                'result_value'   => $existingResults[$t->id]?->result_value ?? '',
                'is_abnormal'    => $existingResults[$t->id]?->is_abnormal ?? false,
                'abnormal_flag'  => $existingResults[$t->id]?->abnormal_flag ?? null,
            ]);

        return inertia('Laboratory/Print', [
            'visit' => [
                'id'               => $visit->id,
                'visit_type'       => $visit->visit_type,
                'visit_date'       => $visit->visit_date->format('M d, Y'),
                'employer_company' => $visit->employer_company,
            ],
            'patient' => [
                'id'           => $patient->id,
                'full_name'    => $patient->full_name,
                'patient_code' => $patient->patient_code,
                'age_sex'      => $patient->age_sex,
                'sex'          => $patient->sex,
                'birthdate'    => $patient->birthdate?->format('M d, Y'),
                'civil_status' => $patient->civil_status ?? '—',
            ],
            'labRequest' => $labRequest ? [
                'request_number'      => $labRequest->request_number,
                'status'              => $labRequest->status,
                'examined_by_name'    => $labRequest->examined_by_name,
                'examined_by_license' => $labRequest->examined_by_license,
                'noted_by_name'       => $labRequest->noted_by_name,
                'noted_by_license'    => $labRequest->noted_by_license,
                'clinical_notes'      => $labRequest->clinical_notes,
            ] : null,
            'tests' => $allTests,
        ]);
    }
}
