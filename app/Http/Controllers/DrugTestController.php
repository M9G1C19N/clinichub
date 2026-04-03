<?php

namespace App\Http\Controllers;

use App\Models\DrugTestRequest;
use App\Models\PatientVisit;
use App\Models\QueueRoomAssignment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DrugTestController extends Controller
{
    // ── DASHBOARD ─────────────────────────────────────
    public function index(Request $request)
    {
        $search = $request->get('search', '');
        $date   = $request->get('date', '');

        // Today's drug test queue — include completed
        $queue = QueueRoomAssignment::with([
            'ticket.patient',
            'ticket.visit.drugTestRequest',
            'ticket.visit.invoice.items',
        ])
        ->today()
        ->forRoom('drug_test')
        ->whereNotIn('status', ['no_show','skipped','cancelled'])
        ->orderByRaw("FIELD(status, 'serving','calling','waiting','completed')")
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
                'has_result'       => $a->ticket->visit?->drugTestRequest !== null,
                'drug_status'      => $a->ticket->visit?->drugTestRequest?->status ?? 'none',
                'is_released'      => $a->ticket->visit?->drugTestRequest?->status === 'released',
                'result'           => $a->ticket->visit?->drugTestRequest?->result,
            ] : null,
        ]);

        // Pending — not yet released
        $pendingQuery = DrugTestRequest::with(['patient','visit'])
            ->whereIn('status', ['pending','processing'])
            ->when($search, fn($q) =>
                $q->whereHas('patient', fn($p) =>
                    $p->where('first_name','like',"%{$search}%")
                      ->orWhere('last_name','like',"%{$search}%")
                      ->orWhere('patient_code','like',"%{$search}%")
                )
            )
            ->latest()
            ->paginate(10, ['*'], 'pending_page')
            ->withQueryString();

        $pending = $pendingQuery->getCollection()->map(fn($r) => [
            'id'           => $r->id,
            'code_number'  => $r->code_number,
            'status'       => $r->status,
            'patient_name' => $r->patient->full_name,
            'patient_code' => $r->patient->patient_code,
            'age_sex'      => $r->patient->age_sex,
            'visit_id'     => $r->patient_visit_id,
            'visit_type'   => $r->visit?->visit_type,
            'visit_date'   => $r->created_at->format('M d, Y'),
            'employer'     => $r->company,
            'drugs_label'  => $r->drugs_label,
            'purpose'      => $r->test_purpose_label,
        ]);
        $pendingQuery->setCollection($pending);

        // History — released
        $historyQuery = DrugTestRequest::with(['patient','visit'])
            ->where('status','released')
            ->when($search, fn($q) =>
                $q->whereHas('patient', fn($p) =>
                    $p->where('first_name','like',"%{$search}%")
                      ->orWhere('last_name','like',"%{$search}%")
                      ->orWhere('patient_code','like',"%{$search}%")
                )
            )
            ->when($date, fn($q) => $q->whereDate('released_at',$date))
            ->latest('released_at')
            ->paginate(10, ['*'], 'history_page')
            ->withQueryString();

        $history = $historyQuery->getCollection()->map(fn($r) => [
            'id'          => $r->id,
            'code_number' => $r->code_number,
            'patient_name'=> $r->patient->full_name,
            'patient_code'=> $r->patient->patient_code,
            'age_sex'     => $r->patient->age_sex,
            'visit_id'    => $r->patient_visit_id,
            'visit_type'  => $r->visit?->visit_type,
            'released_at' => $r->released_at?->format('M d, Y h:i A'),
            'employer'    => $r->company,
            'result'      => $r->result,
            'drugs_label' => $r->drugs_label,
            'purpose'     => $r->test_purpose_label,
        ]);
        $historyQuery->setCollection($history);

        return inertia('DrugTest/Index', [
            'queue'   => $queue,
            'pending' => $pendingQuery,
            'history' => $historyQuery,
            'filters' => ['search' => $search, 'date' => $date],
            'summary' => [
                'today'         => count($queue),
                'pending'       => DrugTestRequest::whereIn('status',['pending','processing'])->count(),
                'released_today'=> DrugTestRequest::where('status','released')
                                    ->whereDate('released_at',today())->count(),
            ],
        ]);
    }

    // ── ENTER RESULT FORM ──────────────────────────────
    public function enter(PatientVisit $visit)
    {
        $visit->load(['patient','invoice.items','drugTestRequest']);

        $currentUser = Auth::user();
        $existing    = $visit->drugTestRequest;

        // Determine drugs to test from ordered services
        $orderedServices = collect($visit->invoice?->items ?? [])
            ->pluck('service_code')->toArray();

        $drugsDefault = ['thc_met']; // default THC & MET
        if (in_array('DRUGTEST5', $orderedServices)) {
            $drugsDefault = ['thc_coc_pcp_opi_amp'];
        }

        return inertia('DrugTest/Enter', [
            'visit' => [
                'id'               => $visit->id,
                'visit_type'       => $visit->visit_type,
                'visit_date'       => $visit->visit_date->format('M d, Y'),
                'employer_company' => $visit->employer_company,
                'services'         => $orderedServices,
            ],
            'patient' => [
                'id'            => $visit->patient->id,
                'full_name'     => $visit->patient->full_name,
                'last_name'     => $visit->patient->last_name,
                'first_name'    => $visit->patient->first_name,
                'middle_name'   => $visit->patient->middle_name ?? '',
                'patient_code'  => $visit->patient->patient_code,
                'age_sex'       => $visit->patient->age_sex,
                'age'           => $visit->patient->age,
                'sex'           => $visit->patient->sex,
                'birthdate'     => $visit->patient->birthdate?->format('m/d/Y'),
                'civil_status'  => $visit->patient->civil_status ?? '',
                'address'       => $visit->patient->address ?? '',
            ],
            'drugTest' => $existing ? [
                'id'                  => $existing->id,
                'code_number'         => $existing->code_number,
                'accession_number'    => $existing->accession_number,
                'status'              => $existing->status,
                'test_purpose'        => $existing->test_purpose,
                'drugs_to_test'       => $existing->drugs_to_test,
                'specimen_type'       => $existing->specimen_type,
                'specimen_time'       => $existing->specimen_time?->format('H:i'),
                'temp_in_range'       => $existing->temp_in_range,
                'specimen_volume'     => $existing->specimen_volume,
                'specimen_appearance' => $existing->specimen_appearance,
                'specimen_sampling'   => $existing->specimen_sampling,
                'specimen_collection' => $existing->specimen_collection,
                'collector_name'       => $existing->collector_name,
                'collector_license'    => $existing->collector_license,
                'collector_signature'  => $existing->collector_signature,
                'head_of_lab_name'     => $existing->head_of_lab_name,
                'head_of_lab_license'  => $existing->head_of_lab_license,
                'head_of_lab_signature'=> $existing->head_of_lab_signature,
                'remarks'             => $existing->remarks,
                'result'              => $existing->result,
                'result_remarks'      => $existing->result_remarks,
                'certification_signed'=> $existing->certification_signed,
                'certification_date'  => $existing->certification_date?->format('Y-m-d'),
                'company'             => $existing->company,
                'specimen_date'       => $existing->specimen_date?->format('Y-m-d') ?? now()->format('Y-m-d'),
            ] : null,
            'currentUser' => [
                'id'         => $currentUser->id,
                'name'       => $currentUser->name,
                'prc_number' => $currentUser->prc_number ?? '',
                'esignature' => $currentUser->esignature ? [
                    'title'          => $currentUser->esignature->title,
                    'license_number' => $currentUser->esignature->license_number,
                    'signature_url'  => $currentUser->esignature->signature_url,
                    'signature_path' => $currentUser->esignature->signature_path,
                ] : null,
            ],
            'drugsDefault'   => $drugsDefault,
            'collectorList'  => $this->getStaffList(['drug_test', 'nursing', 'admin']),
            'headOfLabList'  => $this->getStaffList(['laboratory', 'admin']),
        ]);
    }

    // ── SAVE ──────────────────────────────────────────
    public function save(Request $request, PatientVisit $visit)
    {
        $validated = $request->validate([
            'test_purpose'        => ['required','string'],
            'drugs_to_test'       => ['required','array'],
            'specimen_type'       => ['required','in:urine,blood,other'],
            'specimen_time'       => ['nullable','string'],
            'temp_in_range'       => ['nullable','boolean'],
            'specimen_volume'     => ['nullable','string','max:20'],
            'specimen_appearance' => ['nullable','string','max:100'],
            'specimen_sampling'   => ['nullable','in:single,split'],
            'specimen_collection' => ['nullable','in:observed,unobserved'],
            'collector_name'        => ['nullable','string','max:100'],
            'collector_license'     => ['nullable','string','max:50'],
            'collector_signature'   => ['nullable','string'],
            'head_of_lab_name'      => ['nullable','string','max:100'],
            'head_of_lab_license'   => ['nullable','string','max:50'],
            'head_of_lab_signature' => ['nullable','string'],
            'remarks'             => ['nullable','string'],
            'result'              => ['nullable','in:negative,positive,cancelled,refusal,diluted,substituted,adulterated'],
            'result_remarks'      => ['nullable','string'],
            'certification_signed'=> ['boolean'],
            'certification_date'  => ['nullable','date'],
            'company'             => ['nullable','string'],
            'release'             => ['boolean'],
            'specimen_date'       => ['nullable', 'date'],
        ]);

        DrugTestRequest::updateOrCreate(
            ['patient_visit_id' => $visit->id],
            [
                'patient_id'          => $visit->patient_id,
                'company'             => $validated['company'] ?? $visit->employer_company,
                'test_purpose'        => $validated['test_purpose'],
                'drugs_to_test'       => $validated['drugs_to_test'],
                'specimen_type'       => $validated['specimen_type'],
                'specimen_time'       => $validated['specimen_time']
                                         ? now()->setTimeFromTimeString($validated['specimen_time'])
                                         : null,
                'temp_in_range'       => $validated['temp_in_range'] ?? null,
                'specimen_volume'     => $validated['specimen_volume'] ?? null,
                'specimen_appearance' => $validated['specimen_appearance'] ?? null,
                'specimen_sampling'   => $validated['specimen_sampling'] ?? 'single',
                'specimen_collection' => $validated['specimen_collection'] ?? 'unobserved',
                'collector_name'        => $validated['collector_name'] ?? null,
                'collector_license'     => $validated['collector_license'] ?? null,
                'collector_signature'   => $validated['collector_signature'] ?? null,
                'head_of_lab_name'      => $validated['head_of_lab_name'] ?? null,
                'head_of_lab_license'   => $validated['head_of_lab_license'] ?? null,
                'head_of_lab_signature' => $validated['head_of_lab_signature'] ?? null,
                'collected_by'        => Auth::id(),
                'remarks'             => $validated['remarks'] ?? null,
                'result'              => $validated['result'] ?? null,
                'result_remarks'      => $validated['result_remarks'] ?? null,
                'certification_signed'=> $validated['certification_signed'] ?? false,
                'certification_date'  => $validated['certification_date'] ?? null,
                'status'              => $validated['release'] ? 'released' : 'processing',
                'released_at'         => $validated['release'] ? now() : null,
                'released_by'         => $validated['release'] ? Auth::id() : null,
                'specimen_date'       => $validated['specimen_date'] ?? now()->format('Y-m-d'),

            ]
        );

        return redirect()
            ->route('drug-test.index')
            ->with('success', "Drug test " . ($validated['release'] ? 'released' : 'saved') . " for {$visit->patient->full_name}.");
    }

    // ── PRINT ──────────────────────────────────────────
    public function print(PatientVisit $visit)
    {
        $visit->load(['patient','drugTestRequest']);
        $test    = $visit->drugTestRequest;
        $patient = $visit->patient;

        return inertia('DrugTest/Print', [
            'visit' => [
                'id'               => $visit->id,
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
                'birthdate'    => $patient->birthdate?->format('m/d/Y'),
                'civil_status' => $patient->civil_status ?? '',
                'address'      => $patient->address ?? '',
                'patient_code' => $patient->patient_code,
            ],
            'drugTest' => $test ? [
                'code_number'         => $test->code_number,
                'accession_number'    => $test->accession_number,
                'test_purpose'        => $test->test_purpose,
                'test_purpose_label'  => $test->test_purpose_label,
                'drugs_to_test'       => $test->drugs_to_test,
                'drugs_label'         => $test->drugs_label,
                'specimen_type'       => $test->specimen_type,
                'specimen_time'       => $test->specimen_time?->format('h:i A'),
                'temp_in_range'       => $test->temp_in_range,
                'specimen_volume'     => $test->specimen_volume,
                'specimen_appearance' => $test->specimen_appearance,
                'specimen_sampling'   => $test->specimen_sampling,
                'specimen_collection' => $test->specimen_collection,
                'collector_name'        => $test->collector_name,
                'collector_license'     => $test->collector_license,
                'collector_signature'   => $this->sigUrl($test->collector_signature),
                'head_of_lab_name'      => $test->head_of_lab_name,
                'head_of_lab_license'   => $test->head_of_lab_license,
                'head_of_lab_signature' => $this->sigUrl($test->head_of_lab_signature),
                'remarks'             => $test->remarks,
                'result'              => $test->result,
                'result_remarks'      => $test->result_remarks,
                'certification_signed'=> $test->certification_signed,
                'certification_date'  => $test->certification_date?->format('m/d/Y'),
                'company'             => $test->company,
                'status'              => $test->status,
                'released_at'         => $test->released_at?->format('M d, Y h:i A'),
            ] : null,
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
            if (preg_match('#/storage/(.+)$#', $val, $m)) {
                return asset('storage/' . $m[1]);
            }
            return $val;
        }
        return asset('storage/' . $val);
    }
}
