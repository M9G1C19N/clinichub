<?php

namespace App\Http\Controllers;

use App\Models\Esignature;
use App\Models\LabTest;
use App\Models\LaboratoryRequest;
use App\Models\LaboratoryResult;
use App\Models\PatientVisit;
use App\Models\QueueRoomAssignment;
use App\Models\User;
use App\Services\RoomRoutingEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LaboratoryController extends Controller
{
    // ── LAB DASHBOARD ─────────────────────────────────

    public function index(Request $request)
    {
        $search = $request->get('search', '');
        $dateFilter = $request->get('date', '');
        $statusFilter = $request->get('status', 'all');

        // ── TODAY'S QUEUE ─────────────────────────────
        // Show patients whose queue assignment was for lab TODAY
        // regardless of queue status — they still need results entered
        $todayQueue = QueueRoomAssignment::with([
            'ticket.patient',
            'ticket.visit.labRequest',
            'ticket.visit.invoice.items',
        ])
        ->today()
        ->forRoom('laboratory')
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
                'sex'          => $a->ticket?->patient?->sex ?? 'male',
            ],
            'visit' => $a->ticket?->patient_visit_id ? [
                'id'               => $a->ticket->visit?->id,
                'visit_type'       => $a->ticket->visit?->visit_type,
                'employer_company' => $a->ticket->visit?->employer_company,
                'has_results'      => $a->ticket->visit?->labRequest !== null,
                'lab_status'       => $a->ticket->visit?->labRequest?->status ?? 'none',
                'is_collected'     => in_array($a->ticket->visit?->labRequest?->status, ['collecting','processing','released']),
                'is_released'      => $a->ticket->visit?->labRequest?->status === 'released',
                'collected_at'     => $a->ticket->visit?->labRequest?->collected_at?->format('h:i A'),
                'services'         => collect($a->ticket->visit?->invoice?->items ?? [])
                    ->filter(fn($i) => in_array($i->service_code, [
                        'CBC','UA','FECALYSIS','BLOODTYPING','FBS','RBS','BUN',
                        'CREATININE','URICACID','CHOLESTEROL','TRIGLYCERIDES',
                        'HDLLDL','SGOT','SGPT','HBSAG','VDRL','PREGNANCY','DENGUE',
                        'THYROID','PSA','BLOOD_CHEMISTRY'
                    ]))
                    ->map(fn($i) => ['code' => $i->service_code, 'name' => $i->service_name])
                    ->values()->toArray(),
            ] : null,
        ]);

        // ── PENDING — Collected but not yet released ───
        $pendingQuery = LaboratoryRequest::with(['patient', 'visit'])
            ->whereIn('status', ['pending', 'collecting', 'processing'])
            ->when($search, fn($q) =>
                $q->whereHas('patient', fn($p) =>
                    $p->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('patient_code', 'like', "%{$search}%")
                )
            )
            ->when($statusFilter !== 'all', fn($q) => $q->where('status', $statusFilter))
            ->when($dateFilter, fn($q) => $q->whereDate('created_at', $dateFilter))
            ->latest()
            ->paginate(10, ['*'], 'pending_page')
            ->withQueryString();

        $pending = $pendingQuery->getCollection()->map(fn($r) => [
            'id'             => $r->id,
            'request_number' => $r->request_number,
            'status'         => $r->status,
            'patient_name'   => $r->patient->full_name,
            'patient_code'   => $r->patient->patient_code,
            'age_sex'        => $r->patient->age_sex,
            'visit_id'       => $r->patient_visit_id,
            'visit_type'     => $r->visit?->visit_type,
            'visit_date'     => $r->created_at->format('M d, Y'),
            'employer'       => $r->visit?->employer_company,
            'collected_at'   => $r->collected_at?->format('h:i A'),
        ]);
        $pendingQuery->setCollection($pending);

        // ── HISTORY — Released results (searchable) ───
        $historyQuery = LaboratoryRequest::with(['patient', 'visit'])
            ->where('status', 'released')
            ->when($search, fn($q) =>
                $q->whereHas('patient', fn($p) =>
                    $p->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('patient_code', 'like', "%{$search}%")
                )
            )
            ->when($dateFilter, fn($q) =>
                $q->whereDate('released_at', $dateFilter)
            )
            ->latest('released_at')
            ->paginate(10, ['*'], 'history_page')
            ->withQueryString();

        $history = $historyQuery->getCollection()->map(fn($r) => [
            'id'             => $r->id,
            'request_number' => $r->request_number,
            'patient_name'   => $r->patient->full_name,
            'patient_code'   => $r->patient->patient_code,
            'age_sex'        => $r->patient->age_sex,
            'visit_id'       => $r->patient_visit_id,
            'visit_type'     => $r->visit?->visit_type,
            'released_at'    => $r->released_at?->format('M d, Y h:i A'),
            'employer'       => $r->visit?->employer_company,
        ]);
        $historyQuery->setCollection($history);

        return inertia('Laboratory/Index', [
            'todayQueue' => $todayQueue,
            'pending'    => $pendingQuery,
            'history'    => $historyQuery,
            'filters'    => ['search' => $search, 'date' => $dateFilter, 'status' => $statusFilter],
            'summary' => [
                'today'    => count($todayQueue),
                'pending'  => LaboratoryRequest::whereIn('status', ['pending','collecting','processing'])->count(),
                'released_today' => LaboratoryRequest::where('status','released')
                                    ->whereDate('released_at', today())->count(),
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
            'CBC'             => ['hematology'],
            'UA'              => ['urinalysis'],
            'FECALYSIS'       => ['stool'],
            'BLOODTYPING'     => ['hematology'],  // ← keep only ONE entry
            'BLOOD_CHEMISTRY' => ['chemistry'],
            'FBS'             => ['chemistry'],
            'RBS'             => ['chemistry'],
            'BUN'             => ['chemistry'],
            'CREATININE'      => ['chemistry'],
            'URICACID'        => ['chemistry'],
            'CHOLESTEROL'     => ['chemistry'],
            'TRIGLYCERIDES'   => ['chemistry'],
            'HDLLDL'          => ['chemistry'],
            'SGOT'            => ['chemistry'],
            'SGPT'            => ['chemistry'],
            'HBSAG'           => ['serology'],
            'VDRL'            => ['serology'],
            'PREGNANCY'       => ['serology'],
            'DENGUE'          => ['serology'],
            'THYROID'         => ['thyroid'],
            'PSA'             => ['serology'],
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
        // Get tests for needed categories + specific codes
        $specificTestCodes = [];
        if (in_array('BLOODTYPING', $orderedServices)) $specificTestCodes[] = 'BTYPE';
        if (in_array('HBSAG',       $orderedServices)) $specificTestCodes[] = 'HBSAG';
        if (in_array('VDRL',        $orderedServices)) $specificTestCodes[] = 'VDRL';
        if (in_array('PREGNANCY',   $orderedServices)) $specificTestCodes[] = 'PREG';
        if (in_array('PSA',         $orderedServices)) $specificTestCodes[] = 'PSA';

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
                'id'                    => $labRequest->id,
                'request_number'        => $labRequest->request_number,
                'status'                => $labRequest->status,
                'examined_by_name'      => $labRequest->examined_by_name,
                'examined_by_license'   => $labRequest->examined_by_license,
                'examined_by_signature' => $labRequest->examined_by_signature,
                'noted_by_name'         => $labRequest->noted_by_name,
                'noted_by_license'      => $labRequest->noted_by_license,
                'noted_by_signature'    => $labRequest->noted_by_signature,
                'result_date' => $labRequest->result_date?->format('Y-m-d') ?? now()->format('Y-m-d'),
                'result_time' => $labRequest->result_time ?? now()->format('H:i'),
            ] : null,
            'tests'      => $testData,
            'allTests'   => $allTests,
            'currentUser' => [
                'id'         => $currentUser->id,
                'name'       => $currentUser->name,
                'prc_number' => $currentUser->prc_number ?? '',
                'esignature' => $currentUser->esignature ? [
                    'title'          => $currentUser->esignature->title,
                    'license_number' => $currentUser->esignature->license_number,
                    'ptr_number'     => $currentUser->esignature->ptr_number,
                    'signature_url'  => $currentUser->esignature->signature_url,
                    'signature_path' => $currentUser->esignature->signature_path,
                ] : null,
            ],
            'staffList' => $this->getStaffList(['laboratory', 'doctor', 'admin', 'nursing']),
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
            'examined_by_name'      => ['nullable', 'string', 'max:100'],
            'examined_by_license'   => ['nullable', 'string', 'max:50'],
            'examined_by_signature' => ['nullable', 'string'],
            'noted_by_name'         => ['nullable', 'string', 'max:100'],
            'noted_by_license'      => ['nullable', 'string', 'max:50'],
            'noted_by_signature'    => ['nullable', 'string'],
            'general_remarks'       => ['nullable', 'string'],
            'release'              => ['boolean'],
            'result_date' => ['nullable', 'date'],
            'result_time' => ['nullable', 'string', 'max:10'],
        ]);

        DB::transaction(function () use ($validated, $visit) {

            // Create or get lab request
            $labRequest = LaboratoryRequest::updateOrCreate(
                ['patient_visit_id' => $visit->id],
                [
                    'patient_id'          => $visit->patient_id,
                    'requested_by'        => Auth::id(),
                    'request_date'        => today(),
                    'examined_by_name'      => $validated['examined_by_name'] ?? null,
                    'examined_by_license'   => $validated['examined_by_license'] ?? null,
                    'examined_by_signature' => $validated['examined_by_signature'] ?? null,
                    'noted_by_name'         => $validated['noted_by_name'] ?? null,
                    'noted_by_license'      => $validated['noted_by_license'] ?? null,
                    'noted_by_signature'    => $validated['noted_by_signature'] ?? null,
                    'status'              => $validated['release'] ? 'released' : 'processing',
                    'released_at'         => $validated['release'] ? now() : null,
                    'released_by'         => $validated['release'] ? Auth::id() : null,
                    'clinical_notes'      => $validated['general_remarks'] ?? null,
                    'result_date' => $validated['result_date'] ?? now()->format('Y-m-d'),
                    'result_time' => $validated['result_time'] ?? now()->format('H:i'),
                ]
            );

            // Save each result row
            foreach ($validated['results'] as $row) {
                if ($row['value'] === null || $row['value'] === '') continue;

                $test = LabTest::find($row['test_id']);

                // Auto-determine abnormal flag from normal_range text
                $isAbnormal   = false;
                $abnormalFlag = null;

                if (!$test->is_text_result && is_numeric($row['value'])) {
                    $val   = floatval($row['value']);
                    $range = $test->getNormalRangeForPatient($visit->patient->sex ?? 'general');
                    if ($range && $range !== '—') {
                        if (str_starts_with($range, '<')) {
                            $max = floatval(ltrim($range, '<'));
                            if ($val >= $max) { $isAbnormal = true; $abnormalFlag = 'H'; }
                        } elseif (str_starts_with($range, '>')) {
                            $min = floatval(ltrim($range, '>'));
                            if ($val <= $min) { $isAbnormal = true; $abnormalFlag = 'L'; }
                        } else {
                            $parts = explode('-', $range);
                            if (count($parts) === 2 && is_numeric($parts[0]) && is_numeric($parts[1])) {
                                $min = floatval($parts[0]);
                                $max = floatval($parts[1]);
                                if ($val < $min) { $isAbnormal = true; $abnormalFlag = 'L'; }
                                elseif ($val > $max) { $isAbnormal = true; $abnormalFlag = 'H'; }
                            }
                        }
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

    // ── MARK SAMPLE COLLECTED ──────────────────────────
    // Called when lab staff physically receives the sample.
    // Creates the lab request stub (collecting status) and
    // completes the queue assignment so the next patient can be called.

    public function markCollected(PatientVisit $visit)
    {
        DB::transaction(function () use ($visit) {
            // Create or update lab request as "collecting"
            LaboratoryRequest::updateOrCreate(
                ['patient_visit_id' => $visit->id],
                [
                    'patient_id'   => $visit->patient_id,
                    'requested_by' => Auth::id(),
                    'request_date' => today(),
                    'status'       => 'collecting',
                    'collected_at' => now(),
                ]
            );

            // Complete the queue room assignment → frees queue for next patient
            $assignment = QueueRoomAssignment::where('patient_visit_id', $visit->id)
                ->where('room', 'laboratory')
                ->whereIn('status', ['waiting', 'calling', 'serving'])
                ->first();

            if ($assignment) {
                app(RoomRoutingEngine::class)->completeRoom($assignment);
            }
        });

        return back()->with('success', "Sample collected for {$visit->patient->full_name}. Results can be entered from the Pending tab.");
    }

   public function print(PatientVisit $visit)
    {
        $visit->load(['patient', 'labRequest.results.labTest']);

        $labRequest = $visit->labRequest;
        $results = [];
        $categories = [];

        if ($labRequest) {
            foreach ($labRequest->results as $r) {
                $code = $r->labTest?->test_code;
                $cat  = $r->labTest?->category;
                if ($code) {
                    $results[$code] = [
                        'value'       => $r->result_value,
                        'is_abnormal' => $r->is_abnormal,
                        'flag'        => $r->abnormal_flag,
                    ];
                }
                if ($cat && !in_array($cat, $categories)) {
                    $categories[] = $cat;
                }
            }
        }

        return inertia('Laboratory/Print', [
            'visit'   => [
                'id'               => $visit->id,
                'case_number'      => $visit->case_number,
                'visit_date'       => $visit->visit_date->format('M d, Y'),
                'visit_time'       => $visit->visit_date->format('h:i A'),
                'visit_type'       => $visit->visit_type,
                'employer_company' => $visit->employer_company,
            ],
            'patient' => [
                'id'          => $visit->patient->id,
                'full_name'   => $visit->patient->full_name,
                'last_name'   => $visit->patient->last_name,
                'first_name'  => $visit->patient->first_name,
                'middle_name' => $visit->patient->middle_name,
                'age_sex'     => $visit->patient->age_sex,
                'address'     => $visit->patient->address,
            ],
            'labRequest' => $labRequest ? [
                'id'                    => $labRequest->id,
                'status'                => $labRequest->status,
                'examined_by_name'      => $labRequest->examined_by_name,
                'examined_by_license'   => $labRequest->examined_by_license,
                'examined_by_signature' => $this->sigUrl($labRequest->examined_by_signature),
                'noted_by_name'         => $labRequest->noted_by_name,
                'noted_by_license'      => $labRequest->noted_by_license,
                'noted_by_signature'    => $this->sigUrl($labRequest->noted_by_signature),
                'remarks'               => $labRequest->clinical_notes,
                'result_date' => $labRequest->result_date?->format('M d, Y') ?? $visit->visit_date->format('M d, Y'),
                'result_time' => $labRequest->result_time ?? '',
            ] : null,
            'results'    => $results,
            'categories' => $categories,
        ]);
    }

    private function getStaffList(array $departments): array
    {
        return User::with('esignature')
            ->where('is_active', true)
            ->whereIn('department', $departments)
            ->orderBy('name')
            ->get()
            ->filter(fn($u) => $u->esignature?->is_active ?? true)
            ->map(fn($u) => [
                'id'            => $u->id,
                'name'          => $u->name,
                'department'    => $u->department,
                'title'         => $u->esignature?->title ?? null,
                'license_number'=> $u->esignature?->license_number ?? $u->prc_number ?? '',
                'ptr_number'    => $u->esignature?->ptr_number ?? $u->ptr_number ?? '',
                'signature_url'  => $u->esignature?->signature_url ?? null,
                'signature_path' => $u->esignature?->signature_path ?? null,
            ])
            ->values()
            ->toArray();
    }

    private function sigUrl(?string $val): ?string
    {
        if (!$val) return null;
        if (str_starts_with($val, 'http')) {
            // Legacy full URL — extract path after /storage/
            if (preg_match('#/storage/(.+)$#', $val, $m)) {
                return asset('storage/' . $m[1]);
            }
            return $val;
        }
        return asset('storage/' . $val);
    }
}
