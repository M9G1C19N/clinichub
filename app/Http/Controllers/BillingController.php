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
}
