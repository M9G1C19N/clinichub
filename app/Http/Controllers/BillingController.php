<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BillingController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search', '');
        $status = $request->get('status', '');
        $date   = $request->get('date', '');
        $method = $request->get('method', '');

        $query = Invoice::with(['patient', 'visit', 'payments'])
            ->when($search, fn($q) =>
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('patient', fn($p) =>
                      $p->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name',  'like', "%{$search}%")
                        ->orWhere('patient_code', 'like', "%{$search}%")
                  )
            )
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($date,   fn($q) => $q->whereDate('created_at', $date))
            ->when($method, fn($q) =>
                $q->whereHas('payments', fn($p) => $p->where('method', $method))
            )
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $invoices = $query->getCollection()->map(fn($inv) => [
            'id'              => $inv->id,
            'invoice_number'  => $inv->invoice_number,
            'patient_name'    => $inv->patient->full_name,
            'patient_code'    => $inv->patient->patient_code,
            'visit_type'      => $inv->visit?->visit_type,
            'employer'        => $inv->visit?->employer_company,
            'case_number'     => $inv->visit?->case_number,
            'status'          => $inv->status,
            'total_amount'    => (float) $inv->total_amount,
            'discount_amount' => (float) $inv->discount_amount,
            'paid_amount'     => (float) $inv->paid_amount,
            'balance'         => (float) $inv->balance,
            'payment_methods' => $inv->payments->pluck('method')->unique()->values()->toArray(),
            'created_at'      => $inv->created_at->format('M d, Y'),
            'created_time'    => $inv->created_at->format('h:i A'),
            'paid_at'         => $inv->paid_at?->format('M d, Y h:i A'),
        ]);
        $query->setCollection($invoices);

        // Summary stats
        $summary = [
            'total_today'    => Invoice::whereDate('created_at', today())->sum('total_amount'),
            'collected_today'=> Payment::whereDate('created_at', today())->sum('amount'),
            'unpaid_count'   => Invoice::where('status', 'unpaid')->count(),
            'unpaid_balance' => Invoice::where('status', 'unpaid')->sum('balance'),
            'partial_count'  => Invoice::where('status', 'partial')->count(),
            'partial_balance'=> Invoice::where('status', 'partial')->sum('balance'),
            'paid_today'     => Invoice::where('status', 'paid')
                                    ->whereDate('paid_at', today())->count(),
        ];

        // Revenue by method today
        $byMethod = Payment::whereDate('created_at', today())
            ->selectRaw('method, SUM(amount) as total')
            ->groupBy('method')
            ->pluck('total', 'method')
            ->toArray();

        return inertia('Billing/Index', [
            'invoices' => $query,
            'summary'  => $summary,
            'byMethod' => $byMethod,
            'filters'  => compact('search', 'status', 'date', 'method'),
        ]);
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['patient', 'visit', 'items', 'payments.receivedBy', 'createdBy']);

        return inertia('Billing/Show', [
            'invoice' => [
                'id'              => $invoice->id,
                'invoice_number'  => $invoice->invoice_number,
                'status'          => $invoice->status,
                'total_amount'    => (float) $invoice->total_amount,
                'discount_amount' => (float) $invoice->discount_amount,
                'paid_amount'     => (float) $invoice->paid_amount,
                'balance'         => (float) $invoice->balance,
                'notes'              => $invoice->notes,
                'billed_to_company'  => (bool) $invoice->billed_to_company,
                'created_at'         => $invoice->created_at->format('M d, Y h:i A'),
                'paid_at'            => $invoice->paid_at?->format('M d, Y h:i A'),
                'created_by'         => $invoice->createdBy?->name,
            ],
            'patient' => [
                'id'           => $invoice->patient->id,
                'full_name'    => $invoice->patient->full_name,
                'patient_code' => $invoice->patient->patient_code,
                'age_sex'      => $invoice->patient->age_sex,
                'contact'      => $invoice->patient->contact_number,
            ],
            'visit' => [
                'id'               => $invoice->visit->id,
                'case_number'      => $invoice->visit->case_number,
                'visit_type'       => $invoice->visit->visit_type,
                'employer_company' => $invoice->visit->employer_company,
                'visit_date'       => $invoice->visit->visit_date->format('M d, Y h:i A'),
                'is_field_visit'   => $invoice->visit->is_field_visit,
            ],
            'items' => $invoice->items->map(fn($i) => [
                'id'           => $i->id,
                'service_code' => $i->service_code,
                'service_name' => $i->service_name,
                'unit_price'   => (float) $i->unit_price,
                'quantity'     => $i->quantity,
                'subtotal'     => (float) $i->subtotal,
            ]),
            'payments' => $invoice->payments->map(fn($p) => [
                'id'               => $p->id,
                'amount'           => (float) $p->amount,
                'method'           => $p->method,
                'reference_number' => $p->reference_number,
                'notes'            => $p->notes,
                'received_by'      => $p->receivedBy?->name,
                'created_at'       => $p->created_at->format('M d, Y h:i A'),
            ]),
        ]);
    }

    public function recordPayment(Request $request, Invoice $invoice)
    {
        if ($invoice->status === 'paid') {
            return back()->withErrors(['error' => 'Invoice is already fully paid.']);
        }

        $validated = $request->validate([
            'amount'           => ['required', 'numeric', 'min:0.01', 'max:' . $invoice->balance],
            'method'           => ['required', 'in:cash,gcash,maya,card,philhealth,other'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes'            => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($validated, $invoice) {
            Payment::create([
                'invoice_id'       => $invoice->id,
                'amount'           => $validated['amount'],
                'method'           => $validated['method'],
                'reference_number' => $validated['reference_number'] ?? null,
                'notes'            => $validated['notes'] ?? null,
                'received_by'      => Auth::id(),
            ]);

            $invoice->recalculate();
        });

        return back()->with('success', 'Payment of ₱' . number_format($validated['amount'], 2) . ' recorded.');
    }

    public function applyDiscount(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'discount_amount' => ['required', 'numeric', 'min:0', 'max:' . $invoice->total_amount],
            'notes'           => ['nullable', 'string'],
        ]);

        $invoice->update([
            'discount_amount' => $validated['discount_amount'],
            'notes' => $validated['notes'] ?? $invoice->notes,
        ]);
        $invoice->recalculate();

        return back()->with('success', 'Discount of ₱' . number_format($validated['discount_amount'], 2) . ' applied.');
    }

    public function voidInvoice(Invoice $invoice)
    {
        if ($invoice->paid_amount > 0) {
            return back()->withErrors(['error' => 'Cannot void invoice with existing payments.']);
        }

        $invoice->update(['status' => 'cancelled']);

        return back()->with('success', 'Invoice voided.');
    }

    public function recordCompanyPayment(Request $request)
    {
        $validated = $request->validate([
            'company'          => ['required', 'string'],
            'amount'           => ['required', 'numeric', 'min:0.01'],
            'method'           => ['required', 'in:cash,gcash,maya,card,philhealth,other'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes'            => ['nullable', 'string'],
        ]);

        // Load all unpaid/partial invoices for this company, oldest first
        $invoices = Invoice::with(['visit', 'payments'])
            ->whereHas('visit', fn($v) =>
                $v->where('employer_company', $validated['company'])
            )
            ->whereIn('status', ['unpaid', 'partial'])
            ->oldest()
            ->get();

        $remaining = (float) $validated['amount'];

        DB::transaction(function () use ($invoices, $validated, &$remaining) {
            foreach ($invoices as $invoice) {
                if ($remaining <= 0) break;

                $apply = min($remaining, (float) $invoice->balance);
                if ($apply <= 0) continue;

                Payment::create([
                    'invoice_id'       => $invoice->id,
                    'amount'           => $apply,
                    'method'           => $validated['method'],
                    'reference_number' => $validated['reference_number'] ?? null,
                    'notes'            => ($validated['notes'] ?? '') . ' [Company Payment: ' . $validated['company'] . ']',
                    'received_by'      => Auth::id(),
                ]);

                $invoice->recalculate();
                $remaining -= $apply;
            }
        });

        $applied = (float) $validated['amount'] - $remaining;
        return back()->with('success',
            '₱' . number_format($applied, 2) . ' applied to ' . $validated['company'] . ' invoices.'
            . ($remaining > 0 ? ' ₱' . number_format($remaining, 2) . ' was excess (no more unpaid invoices).' : '')
        );
    }

    public function toggleCompanyBilling(Invoice $invoice)
    {
        $invoice->update(['billed_to_company' => !$invoice->billed_to_company]);

        $state = $invoice->billed_to_company ? 'marked as Company Billing' : 'unmarked from Company Billing';
        return back()->with('success', "Invoice {$invoice->invoice_number} {$state}.");
    }

    public function companyBilling(Request $request)
    {
        $dateFrom      = $request->get('date_from', '');
        $dateTo        = $request->get('date_to', '');
        $visitType     = $request->get('visit_type', '');
        $statusFilter  = $request->get('status', '');
        $companySearch = $request->get('company', '');

        $peTypes = ['pre_employment', 'annual_pe', 'exit_pe'];

        $invoices = Invoice::with(['patient', 'visit', 'payments'])
            ->where(function ($q) use ($peTypes) {
                $q->where('billed_to_company', true)
                  ->orWhere(function ($q2) use ($peTypes) {
                      $q2->whereIn('status', ['unpaid', 'partial'])
                         ->whereHas('visit', fn($v) =>
                             $v->whereIn('visit_type', $peTypes)
                               ->whereNotNull('employer_company')
                               ->where('employer_company', '!=', '')
                         );
                  });
            })
            ->whereNotIn('status', ['cancelled'])
            ->when($visitType, fn($q) =>
                $q->whereHas('visit', fn($v) => $v->where('visit_type', $visitType))
            )
            ->when($statusFilter === 'unpaid', fn($q) => $q->whereIn('status', ['unpaid', 'partial']))
            ->when($statusFilter === 'paid', fn($q) => $q->where('status', 'paid'))
            ->when($dateFrom, fn($q) =>
                $q->whereHas('visit', fn($v) => $v->whereDate('visit_date', '>=', $dateFrom))
            )
            ->when($dateTo, fn($q) =>
                $q->whereHas('visit', fn($v) => $v->whereDate('visit_date', '<=', $dateTo))
            )
            ->latest()
            ->get();

        $grouped = $invoices
            ->filter(fn($inv) => $inv->visit?->employer_company)
            ->groupBy(fn($inv) => $inv->visit->employer_company)
            ->when($companySearch, fn($col) =>
                $col->filter(fn($group, $company) =>
                    str_contains(strtolower($company), strtolower($companySearch))
                )
            )
            ->map(function ($group, $company) {
                $mapped = $group->map(fn($inv) => [
                    'id'                => $inv->id,
                    'invoice_number'    => $inv->invoice_number,
                    'patient_name'      => $inv->patient->full_name,
                    'patient_code'      => $inv->patient->patient_code,
                    'visit_type'        => $inv->visit?->visit_type,
                    'status'            => $inv->status,
                    'total_amount'      => (float) $inv->total_amount,
                    'discount_amount'   => (float) $inv->discount_amount,
                    'paid_amount'       => (float) $inv->paid_amount,
                    'balance'           => (float) $inv->balance,
                    'billed_to_company' => (bool) $inv->billed_to_company,
                    'visit_date'        => $inv->visit?->visit_date?->format('M d, Y'),
                    'created_at'        => $inv->created_at->format('M d, Y'),
                ]);

                $byType = fn($type) => [
                    'count'   => $mapped->where('visit_type', $type)->count(),
                    'balance' => $mapped->where('visit_type', $type)->sum('balance'),
                ];

                return [
                    'company'       => $company,
                    'invoices'      => $mapped->values(),
                    'total_balance' => $mapped->sum('balance'),
                    'total_amount'  => $mapped->sum('total_amount'),
                    'invoice_count' => $mapped->count(),
                    'unpaid_count'  => $mapped->whereIn('status', ['unpaid', 'partial'])->count(),
                    'by_type'       => [
                        'pre_employment' => $byType('pre_employment'),
                        'annual_pe'      => $byType('annual_pe'),
                        'exit_pe'        => $byType('exit_pe'),
                    ],
                ];
            })
            ->sortByDesc('total_balance')
            ->values();

        return inertia('Billing/CompanyBilling', [
            'companies' => $grouped,
            'summary'   => [
                'company_count' => $grouped->count(),
                'total_balance' => $grouped->sum('total_balance'),
                'invoice_count' => $grouped->sum('invoice_count'),
                'by_type'       => [
                    'pre_employment' => [
                        'count'   => $grouped->sum(fn($c) => $c['by_type']['pre_employment']['count']),
                        'balance' => $grouped->sum(fn($c) => $c['by_type']['pre_employment']['balance']),
                    ],
                    'annual_pe' => [
                        'count'   => $grouped->sum(fn($c) => $c['by_type']['annual_pe']['count']),
                        'balance' => $grouped->sum(fn($c) => $c['by_type']['annual_pe']['balance']),
                    ],
                    'exit_pe' => [
                        'count'   => $grouped->sum(fn($c) => $c['by_type']['exit_pe']['count']),
                        'balance' => $grouped->sum(fn($c) => $c['by_type']['exit_pe']['balance']),
                    ],
                ],
            ],
            'filters' => [
                'date_from'  => $dateFrom,
                'date_to'    => $dateTo,
                'visit_type' => $visitType,
                'status'     => $statusFilter,
                'company'    => $companySearch,
            ],
        ]);
    }
}
