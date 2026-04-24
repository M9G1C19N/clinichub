<?php

namespace App\Http\Controllers;

use App\Models\AudiometryResult;
use App\Models\PatientVisit;
use App\Models\QueueRoomAssignment;
use App\Models\User;
use App\Services\RoomRoutingEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AudiometryController extends Controller
{
    // ── DASHBOARD ──────────────────────────────────────────

    public function index(Request $request)
    {
        $search          = $request->get('search', '');
        $dateFilter      = $request->get('date', '');
        $pickupSearch    = $request->get('pickup_search', '');
        $pickupClaim     = $request->get('pickup_claim', 'all');
        $pickupDate      = $request->get('pickup_date', '');

        // ── TODAY'S QUEUE ────────────────────────────────
        // Patients in the lab queue today who have AUDIOMETRY ordered
        $todayQueue = QueueRoomAssignment::with([
            'ticket.patient',
            'ticket.visit.audiometryResult',
            'ticket.visit.invoice.items',
        ])
        ->today()
        ->forRoom('audiometry')
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
                'aud_status'       => $a->ticket->visit?->audiometryResult?->status ?? 'none',
                'is_started'       => $a->ticket->visit?->audiometryResult !== null,
                'is_released'      => $a->ticket->visit?->audiometryResult?->status === 'released',
                'collected_at'     => $a->ticket->visit?->audiometryResult?->collected_at?->format('h:i A'),
            ] : null,
        ]);

        // ── PENDING ──────────────────────────────────────
        $pendingQuery = AudiometryResult::with(['patient', 'visit'])
            ->whereIn('status', ['collecting', 'draft'])
            ->when($search, fn($q) =>
                $q->whereHas('patient', fn($p) =>
                    $p->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name',  'like', "%{$search}%")
                    ->orWhere('patient_code', 'like', "%{$search}%")
                )
            )
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

        // ── HISTORY ───────────────────────────────────────
        $historyQuery = AudiometryResult::with(['patient', 'visit'])
            ->where('status', 'released')
            ->when($search, fn($q) =>
                $q->whereHas('patient', fn($p) =>
                    $p->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name',  'like', "%{$search}%")
                    ->orWhere('patient_code', 'like', "%{$search}%")
                )
            )
            ->when($dateFilter, fn($q) => $q->whereDate('released_at', $dateFilter))
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

        // ── READY FOR PICKUP ──────────────────────────────
        $pickupQuery = AudiometryResult::with(['patient', 'visit', 'claimedBy'])
            ->where('status', 'released')
            ->whereIn('claim_status', ['waiting', 'unclaimed'])
            ->when($pickupSearch, fn($q) =>
                $q->whereHas('patient', fn($p) =>
                    $p->where('first_name', 'like', "%{$pickupSearch}%")
                    ->orWhere('last_name',  'like', "%{$pickupSearch}%")
                    ->orWhere('patient_code', 'like', "%{$pickupSearch}%")
                )
            )
            ->when($pickupClaim !== 'all', fn($q) => $q->where('claim_status', $pickupClaim))
            ->when($pickupDate, fn($q) => $q->whereDate('released_at', $pickupDate))
            ->latest('released_at')
            ->paginate(15, ['*'], 'pickup_page')
            ->withQueryString();

        $pickupCollection = $pickupQuery->getCollection()->map(fn($r) => [
            'id'              => $r->id,
            'request_number'  => $r->request_number,
            'claim_status'    => $r->claim_status,
            'patient_name'    => $r->patient->full_name,
            'patient_code'    => $r->patient->patient_code,
            'age_sex'         => $r->patient->age_sex,
            'visit_id'        => $r->patient_visit_id,
            'visit_type'      => $r->visit?->visit_type,
            'employer'        => $r->visit?->employer_company,
            'released_at'     => $r->released_at?->format('M d, Y h:i A'),
            'claimed_at'      => $r->claimed_at?->format('h:i A'),
            'claimed_by_name' => $r->claimedBy?->name,
        ]);
        $pickupQuery->setCollection($pickupCollection);

        return inertia('Audiometry/Index', [
            'todayQueue'     => $todayQueue,
            'pending'        => $pendingQuery,
            'history'        => $historyQuery,
            'readyForPickup' => $pickupQuery,
            'filters'        => [
                'search'       => $search,
                'date'         => $dateFilter,
                'pickup_search'=> $pickupSearch,
                'pickup_claim' => $pickupClaim,
                'pickup_date'  => $pickupDate,
            ],
            'summary' => [
                'today'          => count($todayQueue),
                'pending'        => AudiometryResult::whereIn('status', ['collecting', 'draft'])->count(),
                'released_today' => AudiometryResult::where('status', 'released')
                                        ->whereDate('released_at', today())->count(),
                'waiting_pickup' => AudiometryResult::where('status', 'released')
                                        ->where('claim_status', 'waiting')->count(),
                'unclaimed'      => AudiometryResult::where('status', 'released')
                                        ->where('claim_status', 'unclaimed')->count(),
            ],
        ]);
    }

    // ── START EXAM ─────────────────────────────────────────

    public function markCollected(PatientVisit $visit)
    {
        DB::transaction(function () use ($visit) {
            AudiometryResult::firstOrCreate(
                ['patient_visit_id' => $visit->id],
                [
                    'patient_id'   => $visit->patient_id,
                    'requested_by' => Auth::id(),
                    'status'       => 'collecting',
                    'collected_at' => now(),
                ]
            );

            // Complete the audiometry room assignment → frees queue for next patient
            $assignment = QueueRoomAssignment::where('patient_visit_id', $visit->id)
                ->where('room', 'audiometry')
                ->whereIn('status', ['waiting', 'calling', 'serving'])
                ->first();

            if ($assignment) {
                app(RoomRoutingEngine::class)->completeRoom($assignment);
            }
        });

        return back()->with('success', "Exam collected for {$visit->patient->full_name}.");
    }

    // ── ENTRY FORM ────────────────────────────────────────

    public function enter(PatientVisit $visit)
    {
        $currentUser = Auth::user();
        $visit->load(['patient', 'invoice.items', 'audiometryResult']);

        $audResult = $visit->audiometryResult;
        $patient   = $visit->patient;

        return inertia('Audiometry/Enter', [
            'visit' => [
                'id'               => $visit->id,
                'visit_type'       => $visit->visit_type,
                'visit_date'       => $visit->visit_date->format('M d, Y'),
                'employer_company' => $visit->employer_company,
                'case_number'      => $visit->case_number,
            ],
            'patient' => [
                'id'           => $patient->id,
                'full_name'    => $patient->full_name,
                'patient_code' => $patient->patient_code,
                'age_sex'      => $patient->age_sex,
                'sex'          => $patient->sex,
                'birthdate'    => $patient->birthdate?->format('m/d/Y'),
                'address'      => $patient->address,
            ],
            'audResult' => $audResult ? [
                'id'             => $audResult->id,
                'request_number' => $audResult->request_number,
                'status'         => $audResult->status,
                // Instrument
                'audiometer_used'        => $audResult->audiometer_used,
                'last_calibrated'        => $audResult->last_calibrated,
                'examiner'               => $audResult->examiner,
                'reason_for_audiometry'  => $audResult->reason_for_audiometry,
                // Case History
                'ch_hearing_loss'        => $audResult->ch_hearing_loss,
                'ch_hearing_loss_when'   => $audResult->ch_hearing_loss_when,
                'ch_hearing_aid'         => $audResult->ch_hearing_aid,
                'ch_better_ear'          => $audResult->ch_better_ear,
                'ch_better_ear_which'    => $audResult->ch_better_ear_which,
                'ch_sudden_progression'  => $audResult->ch_sudden_progression,
                'ch_ringing_noises'      => $audResult->ch_ringing_noises,
                'ch_drainage'            => $audResult->ch_drainage,
                'ch_pain_discomfort'     => $audResult->ch_pain_discomfort,
                'ch_medical_consultation'=> $audResult->ch_medical_consultation,
                // Otoscopic
                'otoscopic_right_ear'    => $audResult->otoscopic_right_ear,
                'otoscopic_left_ear'     => $audResult->otoscopic_left_ear,
                // Right Ear
                're_250'  => $audResult->re_250,  're_500'  => $audResult->re_500,
                're_1000' => $audResult->re_1000, 're_1500' => $audResult->re_1500,
                're_2000' => $audResult->re_2000, 're_3000' => $audResult->re_3000,
                're_4000' => $audResult->re_4000, 're_8000' => $audResult->re_8000,
                're_average' => $audResult->re_average,
                // Left Ear
                'le_250'  => $audResult->le_250,  'le_500'  => $audResult->le_500,
                'le_1000' => $audResult->le_1000, 'le_1500' => $audResult->le_1500,
                'le_2000' => $audResult->le_2000, 'le_3000' => $audResult->le_3000,
                'le_4000' => $audResult->le_4000, 'le_8000' => $audResult->le_8000,
                'le_average' => $audResult->le_average,
                // Remarks
                'remarks_right_ear'   => $audResult->remarks_right_ear,
                'remarks_left_ear'    => $audResult->remarks_left_ear,
                'recommendations'     => $audResult->recommendations,
                // Staff
                'examined_by_name'      => $audResult->examined_by_name,
                'examined_by_license'   => $audResult->examined_by_license,
                'examined_by_signature' => $audResult->examined_by_signature,
                'noted_by_name'         => $audResult->noted_by_name,
                'noted_by_license'      => $audResult->noted_by_license,
                'noted_by_signature'    => $audResult->noted_by_signature,
                // Dates
                'result_date' => $audResult->result_date?->format('Y-m-d') ?? now()->format('Y-m-d'),
                'result_time' => $audResult->result_time ?? now()->format('H:i'),
            ] : null,
            'currentUser' => [
                'id'         => $currentUser->id,
                'name'       => $currentUser->name,
                'esignature' => $currentUser->esignature ? [
                    'title'          => $currentUser->esignature->title,
                    'license_number' => $currentUser->esignature->license_number,
                    'signature_url'  => $currentUser->esignature->signature_url,
                    'signature_path' => $currentUser->esignature->signature_path,
                ] : null,
            ],
            'staffList' => $this->getStaffList(['laboratory', 'doctor', 'admin']),
        ]);
    }

    // ── SAVE RESULTS ──────────────────────────────────────

    public function save(Request $request, PatientVisit $visit)
    {
        $validated = $request->validate([
            'audiometer_used'        => ['nullable', 'string', 'max:100'],
            'last_calibrated'        => ['nullable', 'string', 'max:100'],
            'examiner'               => ['nullable', 'string', 'max:100'],
            'reason_for_audiometry'  => ['nullable', 'string'],
            // Case History
            'ch_hearing_loss'        => ['boolean'],
            'ch_hearing_loss_when'   => ['nullable', 'string', 'max:100'],
            'ch_hearing_aid'         => ['boolean'],
            'ch_better_ear'          => ['boolean'],
            'ch_better_ear_which'    => ['nullable', 'string', 'max:100'],
            'ch_sudden_progression'  => ['boolean'],
            'ch_ringing_noises'      => ['boolean'],
            'ch_drainage'            => ['boolean'],
            'ch_pain_discomfort'     => ['boolean'],
            'ch_medical_consultation'=> ['boolean'],
            // Otoscopic
            'otoscopic_right_ear'    => ['nullable', 'string'],
            'otoscopic_left_ear'     => ['nullable', 'string'],
            // Frequencies
            're_250'  => ['nullable', 'numeric', 'min:-10', 'max:140'],
            're_500'  => ['nullable', 'numeric', 'min:-10', 'max:140'],
            're_1000' => ['nullable', 'numeric', 'min:-10', 'max:140'],
            're_1500' => ['nullable', 'numeric', 'min:-10', 'max:140'],
            're_2000' => ['nullable', 'numeric', 'min:-10', 'max:140'],
            're_3000' => ['nullable', 'numeric', 'min:-10', 'max:140'],
            're_4000' => ['nullable', 'numeric', 'min:-10', 'max:140'],
            're_8000' => ['nullable', 'numeric', 'min:-10', 'max:140'],
            're_average' => ['nullable', 'numeric'],
            'le_250'  => ['nullable', 'numeric', 'min:-10', 'max:140'],
            'le_500'  => ['nullable', 'numeric', 'min:-10', 'max:140'],
            'le_1000' => ['nullable', 'numeric', 'min:-10', 'max:140'],
            'le_1500' => ['nullable', 'numeric', 'min:-10', 'max:140'],
            'le_2000' => ['nullable', 'numeric', 'min:-10', 'max:140'],
            'le_3000' => ['nullable', 'numeric', 'min:-10', 'max:140'],
            'le_4000' => ['nullable', 'numeric', 'min:-10', 'max:140'],
            'le_8000' => ['nullable', 'numeric', 'min:-10', 'max:140'],
            'le_average' => ['nullable', 'numeric'],
            // Remarks
            'remarks_right_ear'   => ['nullable', 'string'],
            'remarks_left_ear'    => ['nullable', 'string'],
            'recommendations'     => ['nullable', 'string'],
            // Staff
            'examined_by_name'      => ['nullable', 'string', 'max:100'],
            'examined_by_license'   => ['nullable', 'string', 'max:50'],
            'examined_by_signature' => ['nullable', 'string'],
            'noted_by_name'         => ['nullable', 'string', 'max:100'],
            'noted_by_license'      => ['nullable', 'string', 'max:50'],
            'noted_by_signature'    => ['nullable', 'string'],
            // Meta
            'result_date' => ['nullable', 'date'],
            'result_time' => ['nullable', 'string', 'max:10'],
            'release'     => ['boolean'],
        ]);

        $isRelease = $validated['release'] ?? false;

        DB::transaction(function () use ($validated, $visit, $isRelease) {
            $existing = AudiometryResult::where('patient_visit_id', $visit->id)->first();

            AudiometryResult::updateOrCreate(
                ['patient_visit_id' => $visit->id],
                array_merge(
                    array_diff_key($validated, ['release' => true]),
                    [
                        'patient_id'   => $visit->patient_id,
                        'requested_by' => $existing?->requested_by ?? Auth::id(),
                        'status'       => $isRelease ? 'released' : 'draft',
                        'collected_at' => $existing?->collected_at ?? now(),
                        'released_at'  => $isRelease ? now() : ($existing?->released_at),
                        'released_by'  => $isRelease ? Auth::id() : ($existing?->released_by),
                        'claim_status' => $isRelease ? 'waiting' : ($existing?->claim_status),
                        'result_date'  => $validated['result_date'] ?? now()->format('Y-m-d'),
                        'result_time'  => $validated['result_time'] ?? now()->format('H:i'),
                    ]
                )
            );
        });

        if ($isRelease) {
            return redirect()
                ->route('audiometry.index')
                ->with('success', "Audiometry result released for {$visit->patient->full_name}.");
        }

        return redirect()
            ->route('audiometry.enter', $visit->id)
            ->with('success', 'Draft saved successfully.');
    }

    // ── PRINT ─────────────────────────────────────────────

    public function print(PatientVisit $visit)
    {
        $visit->load(['patient', 'audiometryResult']);
        $aud = $visit->audiometryResult;

        return inertia('Audiometry/Print', [
            'visit' => [
                'id'               => $visit->id,
                'case_number'      => $visit->case_number,
                'visit_date'       => $visit->visit_date->format('M d, Y'),
                'visit_time'       => $visit->visit_date->format('h:i A'),
                'visit_type'       => $visit->visit_type,
                'employer_company' => $visit->employer_company,
            ],
            'patient' => [
                'id'           => $visit->patient->id,
                'full_name'    => $visit->patient->full_name,
                'last_name'    => $visit->patient->last_name,
                'first_name'   => $visit->patient->first_name,
                'middle_name'  => $visit->patient->middle_name,
                'age_sex'      => $visit->patient->age_sex,
                'birthdate'    => $visit->patient->birthdate?->format('m/d/Y'),
                'address'      => $visit->patient->address,
            ],
            'audResult' => $aud ? [
                'id'             => $aud->id,
                'request_number' => $aud->request_number,
                'status'         => $aud->status,
                'audiometer_used'       => $aud->audiometer_used,
                'last_calibrated'       => $aud->last_calibrated,
                'examiner'              => $aud->examiner,
                'reason_for_audiometry' => $aud->reason_for_audiometry,
                'ch_hearing_loss'        => $aud->ch_hearing_loss,
                'ch_hearing_loss_when'   => $aud->ch_hearing_loss_when,
                'ch_hearing_aid'         => $aud->ch_hearing_aid,
                'ch_better_ear'          => $aud->ch_better_ear,
                'ch_better_ear_which'    => $aud->ch_better_ear_which,
                'ch_sudden_progression'  => $aud->ch_sudden_progression,
                'ch_ringing_noises'      => $aud->ch_ringing_noises,
                'ch_drainage'            => $aud->ch_drainage,
                'ch_pain_discomfort'     => $aud->ch_pain_discomfort,
                'ch_medical_consultation'=> $aud->ch_medical_consultation,
                'otoscopic_right_ear'    => $aud->otoscopic_right_ear,
                'otoscopic_left_ear'     => $aud->otoscopic_left_ear,
                're_250'  => $aud->re_250,  're_500'  => $aud->re_500,
                're_1000' => $aud->re_1000, 're_1500' => $aud->re_1500,
                're_2000' => $aud->re_2000, 're_3000' => $aud->re_3000,
                're_4000' => $aud->re_4000, 're_8000' => $aud->re_8000,
                're_average' => $aud->re_average,
                'le_250'  => $aud->le_250,  'le_500'  => $aud->le_500,
                'le_1000' => $aud->le_1000, 'le_1500' => $aud->le_1500,
                'le_2000' => $aud->le_2000, 'le_3000' => $aud->le_3000,
                'le_4000' => $aud->le_4000, 'le_8000' => $aud->le_8000,
                'le_average' => $aud->le_average,
                'remarks_right_ear'   => $aud->remarks_right_ear,
                'remarks_left_ear'    => $aud->remarks_left_ear,
                'recommendations'     => $aud->recommendations,
                'examined_by_name'      => $aud->examined_by_name,
                'examined_by_license'   => $aud->examined_by_license,
                'examined_by_signature' => $this->sigUrl($aud->examined_by_signature),
                'noted_by_name'         => $aud->noted_by_name,
                'noted_by_license'      => $aud->noted_by_license,
                'noted_by_signature'    => $this->sigUrl($aud->noted_by_signature),
                'result_date' => $aud->result_date?->format('M d, Y') ?? $visit->visit_date->format('M d, Y'),
                'result_time' => $aud->result_time ?? '',
            ] : null,
        ]);
    }

    // ── MARK CLAIMED ──────────────────────────────────────

    public function markClaimed(AudiometryResult $audResult)
    {
        abort_if($audResult->status !== 'released', 403, 'Only released results can be claimed.');
        abort_if($audResult->claim_status === 'claimed', 422, 'Already marked as claimed.');

        $audResult->update([
            'claim_status' => 'claimed',
            'claimed_at'   => now(),
            'claimed_by'   => Auth::id(),
        ]);

        return back()->with('success', "Results for {$audResult->patient->full_name} marked as claimed.");
    }

    // ── MARK UNCLAIMED ────────────────────────────────────

    public function markUnclaimed(AudiometryResult $audResult)
    {
        abort_if($audResult->status !== 'released', 403, 'Only released results can be marked unclaimed.');
        abort_if($audResult->claim_status === 'claimed', 422, 'Already claimed — cannot mark as unclaimed.');

        $audResult->update([
            'claim_status' => 'unclaimed',
            'claimed_at'   => null,
            'claimed_by'   => null,
        ]);

        return back()->with('success', "Results for {$audResult->patient->full_name} marked as unclaimed.");
    }

    // ── HELPERS ───────────────────────────────────────────

    private function getStaffList(array $departments): array
    {
        return User::with('esignature')
            ->where('is_active', true)
            ->whereIn('department', $departments)
            ->orderBy('name')
            ->get()
            ->filter(fn($u) => $u->esignature?->is_active ?? true)
            ->map(fn($u) => [
                'id'             => $u->id,
                'name'           => $u->name,
                'department'     => $u->department,
                'title'          => $u->esignature?->title ?? null,
                'license_number' => $u->esignature?->license_number ?? $u->prc_number ?? '',
                'ptr_number'     => $u->esignature?->ptr_number ?? $u->ptr_number ?? '',
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
