<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class BillingReportController extends Controller
{
    public function index(Request $request)
    {
        // ── Date range resolution ──────────────────────
        $preset = $request->get('preset', 'this_month');
        $from   = $request->get('from');
        $to     = $request->get('to');

        [$dateFrom, $dateTo] = $this->resolveRange($preset, $from, $to);

        // ── Summary totals ─────────────────────────────
        $invoicesInRange = Invoice::whereBetween('created_at', [
            $dateFrom->startOfDay()->copy(),
            $dateTo->copy()->endOfDay(),
        ]);

        $totalBilled     = (clone $invoicesInRange)->where('status', '!=', 'cancelled')->sum('total_amount');
        $totalDiscounts  = (clone $invoicesInRange)->where('status', '!=', 'cancelled')->sum('discount_amount');
        $totalCollected  = Payment::whereBetween('created_at', [
            $dateFrom->copy()->startOfDay(),
            $dateTo->copy()->endOfDay(),
        ])->sum('amount');
        $outstanding     = (clone $invoicesInRange)->whereIn('status', ['unpaid', 'partial'])->sum('balance');
        $invoiceCount    = (clone $invoicesInRange)->where('status', '!=', 'cancelled')->count();
        $paidCount       = (clone $invoicesInRange)->where('status', 'paid')->count();
        $partialCount    = (clone $invoicesInRange)->where('status', 'partial')->count();
        $unpaidCount     = (clone $invoicesInRange)->where('status', 'unpaid')->count();
        $voidCount       = (clone $invoicesInRange)->where('status', 'cancelled')->count();

        // ── Collections by payment method ─────────────
        $byMethod = Payment::whereBetween('created_at', [
            $dateFrom->copy()->startOfDay(),
            $dateTo->copy()->endOfDay(),
        ])
        ->selectRaw('method, COUNT(*) as transactions, SUM(amount) as total')
        ->groupBy('method')
        ->orderByDesc('total')
        ->get()
        ->map(fn($r) => [
            'method'       => $r->method,
            'transactions' => $r->transactions,
            'total'        => (float) $r->total,
        ])
        ->toArray();

        // ── Daily collections breakdown ────────────────
        $days = [];
        $cursor = $dateFrom->copy()->startOfDay();
        $end    = $dateTo->copy()->endOfDay();
        while ($cursor->lte($end)) {
            $dayStart = $cursor->copy()->startOfDay();
            $dayEnd   = $cursor->copy()->endOfDay();
            $dayLabel = $cursor->format('D, M d');
            $dayKey   = $cursor->toDateString();

            $billed     = Invoice::whereBetween('created_at', [$dayStart, $dayEnd])
                            ->where('status', '!=', 'cancelled')->sum('total_amount');
            $collected  = Payment::whereBetween('created_at', [$dayStart, $dayEnd])->sum('amount');
            $discounts  = Invoice::whereBetween('created_at', [$dayStart, $dayEnd])
                            ->where('status', '!=', 'cancelled')->sum('discount_amount');
            $invCount   = Invoice::whereBetween('created_at', [$dayStart, $dayEnd])
                            ->where('status', '!=', 'cancelled')->count();

            $days[] = [
                'date'       => $dayKey,
                'label'      => $dayLabel,
                'invoices'   => $invCount,
                'billed'     => (float) $billed,
                'collected'  => (float) $collected,
                'discounts'  => (float) $discounts,
                'balance'    => (float) max(0, $billed - $discounts - $collected),
            ];

            $cursor->addDay();
        }

        // ── Top services by revenue ────────────────────
        $topServices = InvoiceItem::whereHas('invoice', fn($q) =>
                $q->whereBetween('created_at', [
                    $dateFrom->copy()->startOfDay(),
                    $dateTo->copy()->endOfDay(),
                ])->where('status', '!=', 'cancelled')
            )
            ->selectRaw('service_code, service_name, COUNT(*) as transactions, SUM(subtotal) as revenue')
            ->groupBy('service_code', 'service_name')
            ->orderByDesc('revenue')
            ->limit(15)
            ->get()
            ->map(fn($r) => [
                'service_code' => $r->service_code,
                'service_name' => $r->service_name,
                'transactions' => $r->transactions,
                'revenue'      => (float) $r->revenue,
            ])
            ->toArray();

        // ── Revenue by visit type ──────────────────────
        $byVisitType = Invoice::join('patient_visits', 'invoices.patient_visit_id', '=', 'patient_visits.id')
            ->whereBetween('invoices.created_at', [
                $dateFrom->copy()->startOfDay(),
                $dateTo->copy()->endOfDay(),
            ])
            ->where('invoices.status', '!=', 'cancelled')
            ->selectRaw('patient_visits.visit_type, COUNT(invoices.id) as invoice_count, SUM(invoices.total_amount) as billed, SUM(invoices.paid_amount) as collected')
            ->groupBy('patient_visits.visit_type')
            ->orderByDesc('billed')
            ->get()
            ->map(fn($r) => [
                'visit_type'    => $r->visit_type,
                'invoice_count' => $r->invoice_count,
                'billed'        => (float) $r->billed,
                'collected'     => (float) $r->collected,
            ])
            ->toArray();

        // ── Staff collector summary ────────────────────
        $byCollector = Payment::whereBetween('payments.created_at', [
                $dateFrom->copy()->startOfDay(),
                $dateTo->copy()->endOfDay(),
            ])
            ->join('users', 'payments.received_by', '=', 'users.id')
            ->selectRaw('users.name as collector, COUNT(payments.id) as transactions, SUM(payments.amount) as total')
            ->groupBy('users.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn($r) => [
                'collector'    => $r->collector,
                'transactions' => $r->transactions,
                'total'        => (float) $r->total,
            ])
            ->toArray();

        // ── Status summary ─────────────────────────────
        $statusSummary = [
            ['status' => 'paid',      'label' => 'Paid',      'count' => $paidCount,    'amount' => (float)(clone $invoicesInRange)->where('status', 'paid')->sum('paid_amount')],
            ['status' => 'partial',   'label' => 'Partial',   'count' => $partialCount, 'billed' => (float)(clone $invoicesInRange)->where('status', 'partial')->sum('total_amount'), 'collected' => (float)(clone $invoicesInRange)->where('status', 'partial')->sum('paid_amount'), 'balance' => (float)(clone $invoicesInRange)->where('status', 'partial')->sum('balance')],
            ['status' => 'unpaid',    'label' => 'Unpaid',    'count' => $unpaidCount,  'amount' => (float)(clone $invoicesInRange)->where('status', 'unpaid')->sum('balance')],
            ['status' => 'cancelled', 'label' => 'Void',      'count' => $voidCount,    'amount' => 0.0],
        ];

        return inertia('Billing/Reports', [
            'filters' => [
                'preset' => $preset,
                'from'   => $from ?? $dateFrom->toDateString(),
                'to'     => $to   ?? $dateTo->toDateString(),
            ],
            'dateLabel'     => $this->rangeLabel($preset, $dateFrom, $dateTo),
            'summary' => [
                'total_billed'    => (float) $totalBilled,
                'total_collected' => (float) $totalCollected,
                'total_discounts' => (float) $totalDiscounts,
                'outstanding'     => (float) $outstanding,
                'invoice_count'   => $invoiceCount,
                'paid_count'      => $paidCount,
                'partial_count'   => $partialCount,
                'unpaid_count'    => $unpaidCount,
                'void_count'      => $voidCount,
                'collection_rate' => $totalBilled > 0
                    ? round(($totalCollected / ($totalBilled - $totalDiscounts)) * 100, 1)
                    : 0,
            ],
            'byMethod'      => $byMethod,
            'dailyBreakdown'=> $days,
            'topServices'   => $topServices,
            'byVisitType'   => $byVisitType,
            'byCollector'   => $byCollector,
            'statusSummary' => $statusSummary,
        ]);
    }

    // ── Helpers ────────────────────────────────────────

    private function resolveRange(string $preset, ?string $from, ?string $to): array
    {
        return match($preset) {
            'today'        => [Carbon::today(), Carbon::today()],
            'yesterday'    => [Carbon::yesterday(), Carbon::yesterday()],
            'this_week'    => [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()],
            'last_week'    => [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()],
            'this_month'   => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
            'last_month'   => [Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()],
            'this_year'    => [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()],
            'custom'       => [
                $from ? Carbon::parse($from) : Carbon::now()->startOfMonth(),
                $to   ? Carbon::parse($to)   : Carbon::now(),
            ],
            default        => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
        };
    }

    private function rangeLabel(string $preset, Carbon $from, Carbon $to): string
    {
        return match($preset) {
            'today'      => 'Today — ' . $from->format('F d, Y'),
            'yesterday'  => 'Yesterday — ' . $from->format('F d, Y'),
            'this_week'  => 'This Week (' . $from->format('M d') . ' – ' . $to->format('M d, Y') . ')',
            'last_week'  => 'Last Week (' . $from->format('M d') . ' – ' . $to->format('M d, Y') . ')',
            'this_month' => 'This Month — ' . $from->format('F Y'),
            'last_month' => 'Last Month — ' . $from->format('F Y'),
            'this_year'  => 'This Year — ' . $from->format('Y'),
            default      => $from->format('M d, Y') . ' – ' . $to->format('M d, Y'),
        };
    }
}
