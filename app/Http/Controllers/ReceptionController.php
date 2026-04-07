<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\KioskCheckIn;
use App\Models\PackageDiscount;
use App\Models\Patient;
use App\Models\PatientVisit;
use App\Models\Payment;
use App\Models\QueueCounter;
use App\Models\QueueTicket;
use App\Models\ServiceCatalog;
use App\Services\RoomRoutingEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReceptionController extends Controller
{
    public function __construct(private RoomRoutingEngine $engine) {}

    // ── MAIN DASHBOARD ────────────────────────────────

    public function index(Request $request)
    {
        $activeTab  = $request->get('tab', 'today');
        $search     = $request->get('search', '');
        $statusFilter = $request->get('status', '');
        $dateFilter = $request->get('date', '');

        // ── TODAY'S VISITS ─────────────────────────────
        $todayQuery = PatientVisit::with([
            'patient',
            'invoice.payments',
            'labRequest',
            'imagingRequest',
            'drugTestRequest',
            'vitals',
            'consultation',
        ])
        ->whereDate('visit_date', today())
        ->when($search, fn($q) =>
            $q->whereHas('patient', fn($p) =>
                $p->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('patient_code', 'like', "%{$search}%")
            )
        )
        ->when($statusFilter, fn($q) => $q->where('status', $statusFilter))
        ->latest()
        ->paginate(15, ['*'], 'today_page')
        ->withQueryString();

        $today = $todayQuery->getCollection()->map(fn($v) => $this->transformVisit($v));
        $todayQuery->setCollection($today);

        // ── HISTORY (any date, searchable) ─────────────
        $historyQuery = PatientVisit::with([
            'patient',
            'invoice.payments',
            'labRequest',
            'imagingRequest',
            'drugTestRequest',
            'consultation',
        ])
        ->when($search, fn($q) =>
            $q->whereHas('patient', fn($p) =>
                $p->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('patient_code', 'like', "%{$search}%")
            )
        )
        ->when($dateFilter, fn($q) => $q->whereDate('visit_date', $dateFilter))
        ->when($statusFilter, fn($q) => $q->where('status', $statusFilter))
        ->latest('visit_date')
        ->paginate(15, ['*'], 'history_page')
        ->withQueryString();

        $history = $historyQuery->getCollection()->map(fn($v) => $this->transformVisit($v));
        $historyQuery->setCollection($history);

        // ── PENDING PAYMENT ─────────────────────────────
        $unpaidQuery = PatientVisit::with([
            'patient',
            'invoice.payments',
            'consultation',
        ])
        ->whereHas('invoice', fn($q) =>
            $q->whereIn('status', ['unpaid', 'partial'])
        )
        ->when($search, fn($q) =>
            $q->whereHas('patient', fn($p) =>
                $p->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('patient_code', 'like', "%{$search}%")
            )
        )
        ->latest('visit_date')
        ->paginate(15, ['*'], 'unpaid_page')
        ->withQueryString();

        $unpaid = $unpaidQuery->getCollection()->map(fn($v) => $this->transformVisit($v));
        $unpaidQuery->setCollection($unpaid);

        // Pending kiosk check-ins (patients who self-registered at kiosk today)
        $kioskCheckins = KioskCheckIn::with('patient')
            ->pending()
            ->whereDate('created_at', today())
            ->orderBy('created_at')
            ->get()
            ->map(fn($c) => [
                'id'               => $c->id,
                'patient_id'       => $c->patient_id,
                'patient_name'     => $c->patient->full_name,
                'patient_code'     => $c->patient->patient_code,
                'visit_type'       => $c->visit_type,
                'priority'         => $c->priority,
                'services'         => $c->services_requested,
                'employer_company' => $c->employer_company,
                'chief_complaint'  => $c->chief_complaint,
                'checked_in_at'    => $c->created_at->format('h:i A'),
            ]);

        return inertia('Reception/Index', [
            'today'         => $todayQuery,
            'history'       => $historyQuery,
            'unpaid'        => $unpaidQuery,
            'kioskCheckins' => $kioskCheckins,
            'filters' => [
                'search' => $search,
                'status' => $statusFilter,
                'date'   => $dateFilter,
                'tab'    => $activeTab,
            ],
            'summary' => [
                'total_today'   => PatientVisit::whereDate('visit_date', today())->count(),
                'total_revenue' => '₱ ' . number_format(
                    Payment::whereDate('created_at', today())->sum('amount'), 2
                ),
                'unpaid_count'  => \App\Models\Invoice::whereIn('status', ['unpaid','partial'])->count(),
                'completed'     => PatientVisit::whereDate('visit_date', today())
                                    ->where('status', 'completed')->count(),
                'kiosk_pending' => KioskCheckIn::pending()->whereDate('created_at', today())->count(),
            ],
        ]);
    }

    // ── SHARED TRANSFORM ──────────────────────────────
    private function transformVisit(PatientVisit $v): array
    {
        // Determine journey status for each room
        $services = $v->services_selected ?? [];

        // Lab journey
        $labServices = array_intersect($services, [
            'CBC','UA','FECALYSIS','BLOODTYPING','FBS','RBS','BUN',
            'CREATININE','URICACID','CHOLESTEROL','TRIGLYCERIDES',
            'HDLLDL','SGOT','SGPT','HBSAG','VDRL','PREGNANCY','DENGUE',
            'THYROID','PSA',
        ]);

        // XRay journey
        $xrayServices = array_intersect($services, [
            'CXRPA','UTZ','UTZ_ABDOMEN','UTZ_KUB','UTZ_PELVIS','ECG','XRAY',
        ]);

        // Drug test journey
        $drugServices = array_intersect($services, [
            'DRUGTEST','DRUGTEST_FULL','MET_THC','THC',
        ]);

        return [
            'id'               => $v->id,
            'patient_name'     => $v->patient->full_name,
            'patient_code'     => $v->patient->patient_code,
            'patient_id'       => $v->patient_id,
            'visit_type'       => $v->visit_type,
            'status'           => $v->status,
            'visit_date'       => $v->visit_date->format('M d, Y'),
            'visit_time'       => $v->visit_date->format('h:i A'),
            'employer_company' => $v->employer_company,
            'services_count'   => count($services),
            'services'         => $services,
            'case_number'    => $v->case_number,
            'is_field_visit' => $v->is_field_visit,

            // Invoice
            'invoice' => $v->invoice ? [
                'id'             => $v->invoice->id,
                'invoice_number' => $v->invoice->invoice_number,
                'total_amount'   => $v->invoice->total_amount,
                'balance'        => $v->invoice->balance,
                'paid_amount'    => $v->invoice->paid_amount,
                'status'         => $v->invoice->status,
            ] : null,

            // Journey status per room
            'journey' => [
                'case_number' => $v->case_number,
                'lab' => !empty($labServices) ? [
                    'has_services' => true,
                    'services'     => array_values($labServices),
                    'status'       => $v->labRequest?->status ?? 'none',
                    'is_released'  => $v->labRequest?->status === 'released',
                    'has_abnormal' => $v->labRequest?->results()->where('is_abnormal', true)->exists() ?? false,
                ] : null,

                'xray' => !empty($xrayServices) ? [
                    'has_services'  => true,
                    'services'      => array_values($xrayServices),
                    'status'        => $v->imagingRequest?->status ?? 'none',
                    'is_released'   => $v->imagingRequest?->status === 'released',
                    'imaging_type'  => $v->imagingRequest?->imaging_type_label,
                ] : null,

                'drug_test' => !empty($drugServices) ? [
                    'has_services' => true,
                    'services'     => array_values($drugServices),
                    'status'       => $v->drugTestRequest?->status ?? 'none',
                    'is_released'  => $v->drugTestRequest?->status === 'released',
                    'result'       => $v->drugTestRequest?->result,
                ] : null,

                'doctor' => [
                    'has_vitals'    => $v->vitals !== null,
                    'status'        => $v->consultation?->is_finalized ? 'finalized' :
                                    ($v->consultation ? 'in_progress' : 'pending'),
                    'is_finalized'  => $v->consultation?->is_finalized ?? false,
                    'classification'=> $v->consultation?->pe_classification,
                ],
            ],
        ];
    }

    // ── NEW VISIT FORM ────────────────────────────────

    public function create(Request $request)
    {
        $patient = null;
        if ($request->filled('patient_id')) {
            $patient = Patient::find($request->patient_id);
            if ($patient) {
                $patient = [
                    'id'           => $patient->id,
                    'full_name'    => $patient->full_name,
                    'patient_code' => $patient->patient_code,
                    'age_sex'      => $patient->age_sex,
                    'visit_type'   => $patient->visit_type_default,
                    'philhealth'   => $patient->philhealth_number,
                ];
            }
        }

        // Pre-fill from kiosk check-in if provided
        $checkin = null;
        if ($request->filled('checkin_id')) {
            $kioskCheckin = KioskCheckIn::with('patient')->find($request->checkin_id);
            if ($kioskCheckin && $kioskCheckin->status === 'pending') {
                $checkin = [
                    'id'               => $kioskCheckin->id,
                    'visit_type'       => $kioskCheckin->visit_type,
                    'priority'         => $kioskCheckin->priority,
                    'services'         => $kioskCheckin->services_requested,
                    'employer_company' => $kioskCheckin->employer_company,
                    'chief_complaint'  => $kioskCheckin->chief_complaint,
                ];
                // Ensure patient is pre-filled from the checkin
                if (!$patient) {
                    $p = $kioskCheckin->patient;
                    $patient = [
                        'id'           => $p->id,
                        'full_name'    => $p->full_name,
                        'patient_code' => $p->patient_code,
                        'age_sex'      => $p->age_sex,
                        'visit_type'   => $p->visit_type_default,
                        'philhealth'   => $p->philhealth_number,
                    ];
                }
            }
        }

        // Load services grouped by category
        $services = ServiceCatalog::active()
            ->orderBy('category')
            ->orderBy('service_name')
            ->get()
            ->map(fn($s) => [
                'id'               => $s->id,
                'service_code'     => $s->service_code,
                'service_name'     => $s->service_name,
                'category'         => $s->category,
                'base_price'       => $s->base_price,
                'formatted_price'  => $s->formatted_price,
                'requires_fasting' => $s->requires_fasting,
                'turnaround_hours' => $s->turnaround_hours,
            ]);

        $counters = QueueCounter::active()->get()->map(fn($c) => [
            'id'           => $c->id,
            'counter_name' => $c->counter_name,
            'counter_code' => $c->counter_code,
        ]);


        $packageDiscounts = PackageDiscount::where('is_active', true)
            ->orderBy('id')
            ->get()
            ->map(fn($p) => [
                'id'                   => $p->id,
                'package_key'          => $p->package_key,
                'package_name'         => $p->package_name,
                'service_codes'        => $p->service_codes,
                'package_price'        => (float) $p->package_price,
                'addon_drugtest_price' => $p->addon_drugtest_price !== null ? (float) $p->addon_drugtest_price : null,
            ]);

        return inertia('Reception/Create', [
            'patient'          => $patient,
            'services'         => $services,
            'counters'         => $counters,
            'checkin'          => $checkin,
            'packageDiscounts' => $packageDiscounts,
        ]);
    }

    // ── STORE NEW VISIT + INVOICE + TICKET ────────────

    public function store(Request $request)
    {
        $request->merge([
            'queue_counter_id' => (int) $request->queue_counter_id,
            'discount_amount'  => (float) ($request->discount_amount ?? 0),
        ]);
        $validated = $request->validate([
            'patient_id'         => ['required', 'exists:patients,id'],
            'visit_type'         => ['required', 'in:opd,pre_employment,annual_pe,exit_pe,follow_up,lab_only'],
            'is_field_visit'     => ['boolean'],
            'employer_company'   => ['nullable', 'string', 'max:150'],
            'chief_complaint'    => ['nullable', 'string'],
            'referral_validated' => ['boolean'],
            'services'           => ['required', 'array', 'min:1'],
            'priority'           => ['required', 'in:regular,senior,pwd,pregnant,urgent'],
            'queue_counter_id'   => ['required', 'exists:queue_counters,id'],
            'discount_amount'    => ['nullable', 'numeric', 'min:0'],
            'notes'              => ['nullable', 'string'],
            'checkin_id'         => ['nullable', 'exists:kiosk_checkins,id'],
        ]);

        try {
            [$visit, $invoice, $ticket] = DB::transaction(function () use ($validated) {

                // 1. Create patient visit
                $visit = PatientVisit::create([
                    'patient_id'         => $validated['patient_id'],
                    'visit_type'         => $validated['visit_type'],
                    'employer_company'   => $validated['employer_company'] ?? null,
                    'services_selected'  => $validated['services'],
                    'visit_date'         => now(),
                    'is_field_visit'     => $validated['is_field_visit'] ?? false,
                    'status'             => 'pending',
                    'chief_complaint'    => $validated['chief_complaint'] ?? null,
                    'referral_validated' => $validated['referral_validated'] ?? false,
                    'is_field_visit' => $validated['is_field_visit'] ?? false,
                    'created_by'         => Auth::id(),
                ]);

                // 2. Create invoice
                $invoice = Invoice::create([
                    'patient_id'       => $validated['patient_id'],
                    'patient_visit_id' => $visit->id,
                    'discount_amount'  => $validated['discount_amount'] ?? 0,
                    'notes'            => $validated['notes'] ?? null,
                    'created_by'       => Auth::id(),
                ]);

                // 3. Create invoice items (snapshot pricing)
                $services = ServiceCatalog::whereIn('service_code', $validated['services'])->get();

                foreach ($services as $svc) {
                    InvoiceItem::create([
                        'invoice_id'   => $invoice->id,
                        'service_code' => $svc->service_code,
                        'service_name' => $svc->service_name,
                        'unit_price'   => $svc->base_price,
                        'quantity'     => 1,
                        'subtotal'     => $svc->base_price,
                    ]);
                }

                // 4. Recalculate totals
                $invoice->recalculate();

                //5. Create queue ticket — generate number explicitly inside transaction
                $counter = QueueCounter::find($validated['queue_counter_id']);
                $prefix  = $counter?->counter_code ?? 'A';

                $maxRow = DB::selectOne(
                    "SELECT COALESCE(
                        MAX(CAST(SUBSTRING_INDEX(ticket_number, '-', -1) AS UNSIGNED)),
                    0) as max_num
                    FROM queue_tickets
                    WHERE ticket_number LIKE ?",
                    [$prefix . '-%']
                );

                $next         = (int)($maxRow->max_num ?? 0) + 1;
                $ticketNumber = $prefix . '-' . str_pad($next, 3, '0', STR_PAD_LEFT);

                $ticket = QueueTicket::create([
                    'ticket_number'      => $ticketNumber,
                    'patient_id'         => $validated['patient_id'],
                    'patient_visit_id'   => $visit->id,
                    'queue_counter_id'   => $validated['queue_counter_id'],
                    'visit_type'         => $validated['visit_type'],
                    'priority'           => $validated['priority'],
                    'services_requested' => $validated['services'],
                    'issued_by'          => Auth::id(),
                    'issued_at'          => now(),
                ]);

                // 6. Route ticket
                app(RoomRoutingEngine::class)->route($ticket);

                return [$visit, $invoice, $ticket];
            });

            $ticket->load(['patient', 'roomAssignments']);
            $rooms = $ticket->roomAssignments->map(fn($a) => [
                'room'         => $a->room,
                'queue_number' => $a->queue_number,
            ])->values()->toArray();

            // Mark kiosk check-in as processed if this visit originated from one
            if (!empty($validated['checkin_id'])) {
                KioskCheckIn::where('id', $validated['checkin_id'])
                    ->where('status', 'pending')
                    ->update(['status' => 'processed', 'processed_at' => now()]);
            }

            return redirect()
                ->route('reception.show', $invoice->id)
                ->with('success', "Visit registered! Invoice {$invoice->invoice_number} created. Ticket {$ticket->ticket_number} issued.")
                ->with('newTicket', [
                    'ticket_number' => $ticket->ticket_number,
                    'patient_name'  => $ticket->patient->full_name,
                    'patient_code'  => $ticket->patient->patient_code,
                    'visit_type'    => $ticket->visit_type,
                    'priority'      => $ticket->priority,
                    'services'      => $ticket->services_requested ?? [],
                    'rooms'         => $rooms,
                    'issued_at'     => $ticket->issued_at->format('M d, Y h:i A'),
                ]);

        } catch (\Throwable $e) {
            // Temporarily expose full error for debugging
            return back()->withErrors([
                'error' => $e->getMessage() . ' in ' . $e->getFile() . ' line ' . $e->getLine()
            ])->withInput();
        }
    }

    // ── SHOW INVOICE ──────────────────────────────────

    public function show(Invoice $invoice)
    {
        $invoice->load(['patient', 'visit.queueTicket.roomAssignments', 'items', 'payments.receivedBy', 'createdBy']);

        return inertia('Reception/Show', [
            'invoice' => [
                'id'              => $invoice->id,
                'invoice_number'  => $invoice->invoice_number,
                'status'          => $invoice->status,
                'total_amount'    => $invoice->total_amount,
                'discount_amount' => $invoice->discount_amount,
                'paid_amount'     => $invoice->paid_amount,
                'balance'         => $invoice->balance,
                'notes'           => $invoice->notes,
                'created_at'      => $invoice->created_at->format('M d, Y h:i A'),
                'paid_at'         => $invoice->paid_at?->format('M d, Y h:i A'),
                'created_by'      => $invoice->createdBy?->name,
            ],
            'patient' => [
                'id'           => $invoice->patient->id,
                'full_name'    => $invoice->patient->full_name,
                'patient_code' => $invoice->patient->patient_code,
                'age_sex'      => $invoice->patient->age_sex,
            ],
            'visit' => [
                'id'               => $invoice->visit->id,
                'case_number'      => $invoice->visit->case_number,
                'visit_type'       => $invoice->visit->visit_type,
                'status'           => $invoice->visit->status,
                'employer_company' => $invoice->visit->employer_company,
                'visit_date'       => $invoice->visit->visit_date->format('M d, Y h:i A'),
                'is_field_visit' => $invoice->visit->is_field_visit,
            ],
            'items' => $invoice->items->map(fn($i) => [
                'id'           => $i->id,
                'service_code' => $i->service_code,
                'service_name' => $i->service_name,
                'unit_price'   => $i->unit_price,
                'quantity'     => $i->quantity,
                'subtotal'     => $i->subtotal,
            ]),
            'payments' => $invoice->payments->map(fn($p) => [
                'id'               => $p->id,
                'amount'           => $p->amount,
                'method'           => $p->method,
                'reference_number' => $p->reference_number,
                'received_by'      => $p->receivedBy?->name,
                'created_at'       => $p->created_at->format('M d, Y h:i A'),
            ]),
            'ticket' => $invoice->visit->queueTicket ? [
                'ticket_number' => $invoice->visit->queueTicket->ticket_number,
                'patient_name'  => $invoice->patient->full_name,
                'patient_code'  => $invoice->patient->patient_code,
                'visit_type'    => $invoice->visit->queueTicket->visit_type,
                'priority'      => $invoice->visit->queueTicket->priority,
                'services'      => $invoice->visit->queueTicket->services_requested ?? [],
                'rooms'         => $invoice->visit->queueTicket->roomAssignments->map(fn($a) => [
                    'room'         => $a->room,
                    'queue_number' => $a->queue_number,
                ])->values()->toArray(),
                'issued_at'     => $invoice->visit->queueTicket->issued_at?->format('M d, Y h:i A'),
            ] : null,
        ]);
    }

    // ── RECORD PAYMENT ────────────────────────────────

    public function recordPayment(Request $request, Invoice $invoice)
    {
        $request->validate([
            'amount'           => ['required', 'numeric', 'min:0.01', 'max:' . $invoice->balance],
            'method'           => ['required', 'in:cash,gcash,maya,card,philhealth,other'],
            'reference_number' => ['nullable', 'string', 'max:50'],
            'notes'            => ['nullable', 'string'],
        ]);

        Payment::create([
            'invoice_id'       => $invoice->id,
            'amount'           => $request->amount,
            'method'           => $request->method,
            'reference_number' => $request->reference_number,
            'notes'            => $request->notes,
            'received_by'      => Auth::id(),
        ]);

        $invoice->recalculate();

        return back()->with('success', "Payment of ₱" . number_format($request->amount, 2) . " recorded.");
    }

    // ── SEARCH PATIENT (API) ──────────────────────────

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
                'philhealth'   => $p->philhealth_number,
            ]);

        return response()->json($patients);
    }
}
