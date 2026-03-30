<?php

namespace App\Http\Controllers;

use App\Models\ImagingRequest;
use App\Models\PatientVisit;
use App\Models\QueueRoomAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class XRayController extends Controller
{
    // ── XRAY DASHBOARD ────────────────────────────────

    public function index(Request $request)
    {
        $search     = $request->get('search', '');
        $dateFilter = $request->get('date', '');

        // Today's queue — include completed queue assignments
        // so patients don't disappear after queue is marked done
        $queue = QueueRoomAssignment::with([
            'ticket.patient',
            'ticket.visit.imagingRequest',
            'ticket.visit.invoice.items',
        ])
        ->today()
        ->forRoom('xray_utz')
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
                'has_report'       => $a->ticket->visit?->imagingRequest !== null,
                'imaging_status'   => $a->ticket->visit?->imagingRequest?->status ?? 'none',
                'is_released'      => $a->ticket->visit?->imagingRequest?->status === 'released',
                'services'         => collect($a->ticket->visit?->invoice?->items ?? [])
                    ->filter(fn($i) => in_array($i->service_code, [
                        'CXRPA','UTZ','UTZ_ABDOMEN','UTZ_KUB','UTZ_PELVIS','ECG','XRAY'
                    ]))
                    ->map(fn($i) => ['code' => $i->service_code, 'name' => $i->service_name])
                    ->values()->toArray(),
            ] : null,
        ]);

        // Pending reports — any date, not yet released
        $pendingQuery = ImagingRequest::with(['patient', 'visit'])
            ->whereIn('status', ['pending', 'processing'])
            ->when($search, fn($q) =>
                $q->whereHas('patient', fn($p) =>
                    $p->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('patient_code', 'like', "%{$search}%")
                )
            )
            ->latest()
            ->paginate(10, ['*'], 'pending_page')
            ->withQueryString();

        $pending = $pendingQuery->getCollection()->map(fn($r) => [
            'id'               => $r->id,
            'request_number'   => $r->request_number,
            'imaging_type'     => $r->imaging_type_label,
            'status'           => $r->status,
            'patient_name'     => $r->patient->full_name,
            'patient_code'     => $r->patient->patient_code,
            'age_sex'          => $r->patient->age_sex,
            'visit_id'         => $r->patient_visit_id,
            'visit_type'       => $r->visit?->visit_type,
            'visit_date'       => $r->created_at->format('M d, Y'),
            'employer'         => $r->visit?->employer_company,
            'is_provisional'   => $r->is_provisional,
        ]);
        $pendingQuery->setCollection($pending);

        // History — released, searchable by date
        $historyQuery = ImagingRequest::with(['patient', 'visit'])
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
            'imaging_type'   => $r->imaging_type_label,
            'patient_name'   => $r->patient->full_name,
            'patient_code'   => $r->patient->patient_code,
            'age_sex'        => $r->patient->age_sex,
            'visit_id'       => $r->patient_visit_id,
            'visit_type'     => $r->visit?->visit_type,
            'released_at'    => $r->released_at?->format('M d, Y h:i A'),
            'employer'       => $r->visit?->employer_company,
            'impression'     => $r->impression,
        ]);
        $historyQuery->setCollection($history);

        return inertia('XRay/Index', [
            'queue'   => $queue,
            'pending' => $pendingQuery,
            'history' => $historyQuery,
            'filters' => ['search' => $search, 'date' => $dateFilter],
            'summary' => [
                'today'         => count($queue),
                'pending'       => ImagingRequest::whereIn('status', ['pending','processing'])->count(),
                'released_today'=> ImagingRequest::where('status','released')
                                ->whereDate('released_at', today())->count(),
            ],
        ]);
    }

    // ── ENTER FINDINGS FORM ───────────────────────────

    public function enter(PatientVisit $visit)
    {
        $visit->load(['patient', 'invoice.items', 'imagingRequest']);

        $currentUser = Auth::user();
        $existing    = $visit->imagingRequest;

        // Determine imaging types ordered
        $imagingServiceMap = [
            'CXRPA'       => 'chest_xray_pa',
            'UTZ'         => 'ultrasound_abdomen',
            'UTZ_ABDOMEN' => 'ultrasound_abdomen',
            'UTZ_KUB'     => 'kub',
            'UTZ_PELVIS'  => 'ultrasound_pelvis',
            'ECG'         => 'ecg',
            'XRAY'        => 'chest_xray_pa',
        ];

        $orderedServices = collect($visit->invoice?->items ?? [])
            ->pluck('service_code')->toArray();

        // Get primary imaging type from ordered services
        $primaryImagingType = 'chest_xray_pa';
        foreach ($orderedServices as $code) {
            if (isset($imagingServiceMap[$code])) {
                $primaryImagingType = $imagingServiceMap[$code];
                break;
            }
        }

        return inertia('XRay/Enter', [
            'visit' => [
                'id'               => $visit->id,
                'visit_type'       => $visit->visit_type,
                'visit_date'       => $visit->visit_date->format('M d, Y'),
                'employer_company' => $visit->employer_company,
                'services'         => $orderedServices,
                'primary_imaging'  => $primaryImagingType,
            ],
            'patient' => [
                'id'           => $visit->patient->id,
                'full_name'    => $visit->patient->full_name,
                'patient_code' => $visit->patient->patient_code,
                'age_sex'      => $visit->patient->age_sex,
                'address'      => $visit->patient->address ?? '',
                'birthdate'    => $visit->patient->birthdate?->format('M d, Y'),
            ],
            'imagingRequest' => $existing ? [
                'id'                   => $existing->id,
                'request_number'       => $existing->request_number,
                'imaging_type'         => $existing->imaging_type,
                'imaging_type_label'   => $existing->imaging_type_label,
                'radiographic_findings'=> $existing->radiographic_findings,
                'impression'           => $existing->impression,
                'is_provisional'       => $existing->is_provisional,
                'status'               => $existing->status,
                'rad_tech_name'        => $existing->rad_tech_name,
                'rad_tech_license'     => $existing->rad_tech_license,
                'radiologist_name'     => $existing->radiologist_name,
                'radiologist_license'  => $existing->radiologist_license,
            ] : null,
            'currentUser' => [
                'name'       => $currentUser->name,
                'prc_number' => $currentUser->prc_number ?? '',
            ],
        ]);
    }

    // ── SAVE FINDINGS ──────────────────────────────────

    public function saveFindings(Request $request, PatientVisit $visit)
    {
        $validated = $request->validate([
            'imaging_type'          => ['required', 'in:chest_xray_pa,kub,ultrasound_abdomen,ultrasound_ob,ultrasound_pelvis,ecg,other'],
            'radiographic_findings' => ['nullable', 'string'],
            'impression'            => ['nullable', 'string'],
            'is_provisional'        => ['boolean'],
            'rad_tech_name'         => ['nullable', 'string', 'max:100'],
            'rad_tech_license'      => ['nullable', 'string', 'max:50'],
            'radiologist_name'      => ['nullable', 'string', 'max:100'],
            'radiologist_license'   => ['nullable', 'string', 'max:50'],
            'release'               => ['boolean'],
        ]);

        ImagingRequest::updateOrCreate(
            ['patient_visit_id' => $visit->id],
            [
                'patient_id'            => $visit->patient_id,
                'requested_by'          => Auth::id(),
                'imaging_type'          => $validated['imaging_type'],
                'radiographic_findings' => $validated['radiographic_findings'] ?? null,
                'impression'            => $validated['impression'] ?? null,
                'is_provisional'        => $validated['is_provisional'] ?? false,
                'rad_tech_name'         => $validated['rad_tech_name'] ?? null,
                'rad_tech_license'      => $validated['rad_tech_license'] ?? null,
                'radiologist_name'      => $validated['radiologist_name'] ?? null,
                'radiologist_license'   => $validated['radiologist_license'] ?? null,
                'status'                => $validated['release'] ? 'released' : 'processing',
                'released_at'           => $validated['release'] ? now() : null,
                'released_by'           => $validated['release'] ? Auth::id() : null,
            ]
        );

        $action = $validated['release'] ? 'released' : 'saved';

        return redirect()
            ->route('xray.index')
            ->with('success', "Imaging report {$action} for {$visit->patient->full_name}.");
    }

    // ── PRINT ─────────────────────────────────────────

    public function print(PatientVisit $visit)
    {
        $visit->load(['patient', 'imagingRequest', 'invoice.items']);

        $imaging  = $visit->imagingRequest;
        $patient  = $visit->patient;

        // Get requesting physician from doctor consultation or default
        $requestingPhysician = 'DR. ROLAND E. MIRA';

        return inertia('XRay/Print', [
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
                'age_sex'      => $patient->age_sex,
                'address'      => $patient->address ?? '',
                'patient_code' => $patient->patient_code,
            ],
            'imagingRequest' => $imaging ? [
                'request_number'       => $imaging->request_number,
                'imaging_type'         => $imaging->imaging_type,
                'imaging_type_label'   => $imaging->imaging_type_label,
                'radiographic_findings'=> $imaging->radiographic_findings,
                'impression'           => $imaging->impression,
                'is_provisional'       => $imaging->is_provisional,
                'rad_tech_name'        => $imaging->rad_tech_name,
                'rad_tech_license'     => $imaging->rad_tech_license,
                'radiologist_name'     => $imaging->radiologist_name,
                'radiologist_license'  => $imaging->radiologist_license,
                'status'               => $imaging->status,
            ] : null,
            'requestingPhysician' => $requestingPhysician,
        ]);
    }
}
