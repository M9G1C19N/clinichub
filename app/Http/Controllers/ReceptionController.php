<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
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
        // Today's visits with invoices
        $visits = PatientVisit::with(['patient', 'invoice.payments'])
            ->whereDate('visit_date', today())
            ->when($request->filled('search'), fn($q) =>
                $q->whereHas('patient', fn($q) =>
                    $q->where('first_name', 'like', "%{$request->search}%")
                      ->orWhere('last_name', 'like', "%{$request->search}%")
                      ->orWhere('patient_code', 'like', "%{$request->search}%")
                )
            )
            ->when($request->filled('status'), fn($q) =>
                $q->where('status', $request->status)
            )
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $transformed = $visits->getCollection()->map(fn($v) => [
            'id'               => $v->id,
            'patient_name'     => $v->patient->full_name,
            'patient_code'     => $v->patient->patient_code,
            'patient_id'       => $v->patient_id,
            'visit_type'       => $v->visit_type,
            'status'           => $v->status,
            'visit_date'       => $v->visit_date->format('h:i A'),
            'services_count'   => count($v->services_selected ?? []),
            'employer_company' => $v->employer_company,
            'invoice'          => $v->invoice ? [
                'id'             => $v->invoice->id,
                'invoice_number' => $v->invoice->invoice_number,
                'total_amount'   => '₱ ' . number_format($v->invoice->total_amount, 2),
                'balance'        => '₱ ' . number_format($v->invoice->balance, 2),
                'status'         => $v->invoice->status,
            ] : null,
        ]);

        $visits->setCollection($transformed);

        // Today's summary
        $summary = [
            'total_visits'   => PatientVisit::whereDate('visit_date', today())->count(),
            'total_revenue'  => '₱ ' . number_format(
                Payment::whereDate('created_at', today())->sum('amount'), 2
            ),
            'unpaid'         => Invoice::whereDate('created_at', today())
                                    ->where('status', 'unpaid')->count(),
            'completed'      => PatientVisit::whereDate('visit_date', today())
                                    ->where('status', 'completed')->count(),
        ];

        return inertia('Reception/Index', [
            'visits'   => $visits,
            'summary'  => $summary,
            'filters'  => $request->only(['search', 'status']),
        ]);
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

        return inertia('Reception/Create', [
            'patient'  => $patient,
            'services' => $services,
            'counters' => $counters,
        ]);
    }

    // ── STORE NEW VISIT + INVOICE + TICKET ────────────

    public function store(Request $request)
{
    $validated = $request->validate([
        'patient_id'         => ['required', 'exists:patients,id'],
        'visit_type'         => ['required', 'in:opd,pre_employment,follow_up,lab_only'],
        'employer_company'   => ['nullable', 'string', 'max:150'],
        'chief_complaint'    => ['nullable', 'string'],
        'referral_validated' => ['boolean'],
        'services'           => ['required', 'array', 'min:1'],
        'services.*'         => ['exists:service_catalog,service_code'],
        'priority'           => ['required', 'in:regular,senior,pwd,pregnant,urgent'],
        'queue_counter_id'   => ['required', 'exists:queue_counters,id'],
        'discount_amount'    => ['nullable', 'numeric', 'min:0'],
        'notes'              => ['nullable', 'string'],
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
                'status'             => 'pending',
                'chief_complaint'    => $validated['chief_complaint'] ?? null,
                'referral_validated' => $validated['referral_validated'] ?? false,
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

            // 5. Create queue ticket
            $ticket = QueueTicket::create([
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

            return [$visit, $invoice, $ticket]; // ✅ important
        });

        return redirect()
            ->route('reception.show', $invoice->id)
            ->with(
                'success',
                "Visit registered! Invoice {$invoice->invoice_number} created. Ticket {$ticket->ticket_number} issued."
            );

    } catch (\Throwable $e) {
        report($e);

        return back()->withErrors([
            'error' => 'Something went wrong while processing the visit. Please try again.'
        ])->withInput();
    }
}

    // ── SHOW INVOICE ──────────────────────────────────

    public function show(Invoice $invoice)
    {
        $invoice->load(['patient', 'visit', 'items', 'payments.receivedBy', 'createdBy']);

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
                'visit_type'       => $invoice->visit->visit_type,
                'status'           => $invoice->visit->status,
                'employer_company' => $invoice->visit->employer_company,
                'visit_date'       => $invoice->visit->visit_date->format('M d, Y h:i A'),
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
