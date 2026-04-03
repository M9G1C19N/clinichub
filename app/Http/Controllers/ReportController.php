<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\DrugTestRequest;
use App\Models\ImagingRequest;
use App\Models\Invoice;
use App\Models\LaboratoryRequest;
use App\Models\Patient;
use App\Models\PatientVisit;
use App\Models\Payment;
use App\Models\QueueTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $preset = $request->get('preset', 'this_month');
        $from   = $request->get('from');
        $to     = $request->get('to');

        [$dateFrom, $dateTo] = $this->resolveRange($preset, $from, $to);
        $start = $dateFrom->copy()->startOfDay();
        $end   = $dateTo->copy()->endOfDay();

        return inertia('Reports/Index', [
            'filters'    => [
                'preset' => $preset,
                'from'   => $from ?? $dateFrom->toDateString(),
                'to'     => $to   ?? $dateTo->toDateString(),
            ],
            'dateLabel'  => $this->rangeLabel($preset, $dateFrom, $dateTo),
            'overview'   => $this->overview($start, $end),
            'patients'   => $this->patientStats($start, $end),
            'clinical'   => $this->clinicalStats($start, $end),
            'financial'  => $this->financialStats($start, $end),
            'queue'      => $this->queueStats($start, $end),
            'trend'      => $this->dailyTrend($dateFrom, $dateTo),
        ]);
    }

    // ── Overview ───────────────────────────────────────────

    private function overview(Carbon $start, Carbon $end): array
    {
        $totalPatients    = Patient::count();
        $newPatients      = Patient::whereBetween('created_at', [$start, $end])->count();
        $totalVisits      = PatientVisit::whereBetween('created_at', [$start, $end])->count();
        $completedVisits  = PatientVisit::whereBetween('created_at', [$start, $end])
                                ->where('status', 'completed')->count();
        $pendingVisits    = PatientVisit::whereBetween('created_at', [$start, $end])
                                ->where('status', 'pending')->count();

        $revenue          = Payment::whereBetween('created_at', [$start, $end])->sum('amount');
        $billed           = Invoice::whereBetween('created_at', [$start, $end])
                                ->where('status', '!=', 'cancelled')->sum('total_amount');
        $outstanding      = Invoice::whereIn('status', ['unpaid', 'partial'])->sum('balance');

        $labRequests      = LaboratoryRequest::whereBetween('created_at', [$start, $end])->count();
        $imagingRequests  = ImagingRequest::whereBetween('created_at', [$start, $end])->count();
        $drugTests        = DrugTestRequest::whereBetween('created_at', [$start, $end])->count();
        $consultations    = Consultation::whereBetween('created_at', [$start, $end])->count();
        $tickets          = QueueTicket::whereBetween('created_at', [$start, $end])->count();
        $appointments     = Appointment::whereBetween('created_at', [$start, $end])->count();

        return compact(
            'totalPatients', 'newPatients', 'totalVisits', 'completedVisits', 'pendingVisits',
            'revenue', 'billed', 'outstanding',
            'labRequests', 'imagingRequests', 'drugTests', 'consultations',
            'tickets', 'appointments'
        );
    }

    // ── Patient Stats ──────────────────────────────────────

    private function patientStats(Carbon $start, Carbon $end): array
    {
        // Sex breakdown — all registered patients
        $bySex = Patient::selectRaw('sex, COUNT(*) as count')
            ->groupBy('sex')
            ->pluck('count', 'sex')
            ->toArray();

        // Age group — all patients
        $ageGroups = Patient::selectRaw("
            CASE
                WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) < 18  THEN 'Under 18'
                WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) < 30  THEN '18–29'
                WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) < 40  THEN '30–39'
                WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) < 50  THEN '40–49'
                WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) < 60  THEN '50–59'
                ELSE '60 and above'
            END as age_group,
            COUNT(*) as count
        ")
        ->groupBy('age_group')
        ->orderByRaw("MIN(TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()))")
        ->get()
        ->map(fn($r) => ['group' => $r->age_group, 'count' => $r->count])
        ->toArray();

        // New patient registrations in period
        $newRegistrations = Patient::whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn($r) => ['date' => $r->date, 'count' => $r->count])
            ->toArray();

        // Visit type breakdown in period
        $byVisitType = PatientVisit::whereBetween('created_at', [$start, $end])
            ->selectRaw('visit_type, COUNT(*) as count')
            ->groupBy('visit_type')
            ->orderByDesc('count')
            ->get()
            ->map(fn($r) => ['visit_type' => $r->visit_type, 'count' => $r->count])
            ->toArray();

        // Top companies (PE/Annual/Exit) in period
        $topCompanies = PatientVisit::whereBetween('created_at', [$start, $end])
            ->whereIn('visit_type', ['pre_employment', 'annual_pe', 'exit_pe'])
            ->whereNotNull('employer_company')
            ->where('employer_company', '!=', '')
            ->selectRaw('employer_company, COUNT(*) as visits')
            ->groupBy('employer_company')
            ->orderByDesc('visits')
            ->limit(10)
            ->get()
            ->map(fn($r) => ['company' => $r->employer_company, 'visits' => $r->visits])
            ->toArray();

        // Repeat vs new visitors in period
        $visitPatientIds = PatientVisit::whereBetween('created_at', [$start, $end])
            ->pluck('patient_id')->unique();
        $newVisitorCount = 0;
        $repeatCount     = 0;
        foreach ($visitPatientIds as $pid) {
            $first = PatientVisit::where('patient_id', $pid)->min('created_at');
            if (Carbon::parse($first)->between($start, $end)) {
                $newVisitorCount++;
            } else {
                $repeatCount++;
            }
        }

        return compact(
            'bySex', 'ageGroups', 'newRegistrations',
            'byVisitType', 'topCompanies',
            'newVisitorCount', 'repeatCount'
        );
    }

    // ── Clinical Stats ─────────────────────────────────────

    private function clinicalStats(Carbon $start, Carbon $end): array
    {
        // Lab requests status breakdown
        $labByStatus = LaboratoryRequest::whereBetween('created_at', [$start, $end])
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Imaging (X-Ray/UTZ) status
        $imagingByStatus = ImagingRequest::whereBetween('created_at', [$start, $end])
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Imaging by type
        $imagingByModality = ImagingRequest::whereBetween('created_at', [$start, $end])
            ->selectRaw('imaging_type, COUNT(*) as count')
            ->groupBy('imaging_type')
            ->pluck('count', 'imaging_type')
            ->toArray();

        // Drug test status
        $drugTestByStatus = DrugTestRequest::whereBetween('created_at', [$start, $end])
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Consultations by type
        $consultByType = Consultation::whereBetween('created_at', [$start, $end])
            ->selectRaw('visit_type, COUNT(*) as count')
            ->groupBy('visit_type')
            ->pluck('count', 'visit_type')
            ->toArray();

        // PE classifications breakdown
        $peClassifications = Consultation::whereBetween('created_at', [$start, $end])
            ->whereIn('visit_type', ['pre_employment', 'annual_pe', 'exit_pe'])
            ->whereNotNull('pe_classification')
            ->selectRaw('pe_classification, COUNT(*) as count')
            ->groupBy('pe_classification')
            ->orderByDesc('count')
            ->get()
            ->map(fn($r) => ['classification' => $r->pe_classification, 'count' => $r->count])
            ->toArray();

        // Pending work (overall unfinished)
        $pendingLab     = LaboratoryRequest::where('status', 'pending')->count();
        $pendingImaging = ImagingRequest::where('status', 'pending')->count();
        $pendingDrug    = DrugTestRequest::where('status', 'pending')->count();

        return compact(
            'labByStatus', 'imagingByStatus', 'imagingByModality',
            'drugTestByStatus', 'consultByType', 'peClassifications',
            'pendingLab', 'pendingImaging', 'pendingDrug'
        );
    }

    // ── Financial Stats ────────────────────────────────────

    private function financialStats(Carbon $start, Carbon $end): array
    {
        $totalBilled     = (float) Invoice::whereBetween('created_at', [$start, $end])
                            ->where('status', '!=', 'cancelled')->sum('total_amount');
        $totalCollected  = (float) Payment::whereBetween('created_at', [$start, $end])->sum('amount');
        $totalDiscounts  = (float) Invoice::whereBetween('created_at', [$start, $end])
                            ->where('status', '!=', 'cancelled')->sum('discount_amount');
        $totalOutstanding = (float) Invoice::whereIn('status', ['unpaid', 'partial'])->sum('balance');

        $invoiceCount    = Invoice::whereBetween('created_at', [$start, $end])
                            ->where('status', '!=', 'cancelled')->count();
        $paidCount       = Invoice::whereBetween('created_at', [$start, $end])
                            ->where('status', 'paid')->count();

        $collectionRate  = ($totalBilled - $totalDiscounts) > 0
            ? round(($totalCollected / ($totalBilled - $totalDiscounts)) * 100, 1)
            : 0;

        // By payment method
        $byMethod = Payment::whereBetween('created_at', [$start, $end])
            ->selectRaw('method, COUNT(*) as transactions, SUM(amount) as total')
            ->groupBy('method')
            ->orderByDesc('total')
            ->get()
            ->map(fn($r) => [
                'method'       => $r->method,
                'transactions' => (int) $r->transactions,
                'total'        => (float) $r->total,
            ])
            ->toArray();

        // By visit type revenue
        $byVisitType = Invoice::join('patient_visits', 'invoices.patient_visit_id', '=', 'patient_visits.id')
            ->whereBetween('invoices.created_at', [$start, $end])
            ->where('invoices.status', '!=', 'cancelled')
            ->selectRaw('patient_visits.visit_type, COUNT(invoices.id) as count, SUM(invoices.total_amount) as billed, SUM(invoices.paid_amount) as collected')
            ->groupBy('patient_visits.visit_type')
            ->orderByDesc('billed')
            ->get()
            ->map(fn($r) => [
                'visit_type' => $r->visit_type,
                'count'      => (int) $r->count,
                'billed'     => (float) $r->billed,
                'collected'  => (float) $r->collected,
            ])
            ->toArray();

        return compact(
            'totalBilled', 'totalCollected', 'totalDiscounts', 'totalOutstanding',
            'invoiceCount', 'paidCount', 'collectionRate',
            'byMethod', 'byVisitType'
        );
    }

    // ── Queue Stats ────────────────────────────────────────

    private function queueStats(Carbon $start, Carbon $end): array
    {
        $total    = QueueTicket::whereBetween('created_at', [$start, $end])->count();
        $done     = QueueTicket::whereBetween('created_at', [$start, $end])
                        ->where('status', 'completed')->count();
        $noShow   = QueueTicket::whereBetween('created_at', [$start, $end])
                        ->where('status', 'no_show')->count();
        $cancelled = QueueTicket::whereBetween('created_at', [$start, $end])
                        ->where('status', 'cancelled')->count();

        // By counter
        $byCounter = QueueTicket::whereBetween('queue_tickets.created_at', [$start, $end])
            ->join('queue_counters', 'queue_tickets.queue_counter_id', '=', 'queue_counters.id')
            ->selectRaw('queue_counters.counter_name, queue_counters.counter_code, COUNT(queue_tickets.id) as total,
                SUM(CASE WHEN queue_tickets.status = "completed" THEN 1 ELSE 0 END) as completed')
            ->groupBy('queue_counters.id', 'queue_counters.counter_name', 'queue_counters.counter_code')
            ->orderByDesc('total')
            ->get()
            ->map(fn($r) => [
                'counter_name' => $r->counter_name,
                'counter_code' => $r->counter_code,
                'total'        => (int) $r->total,
                'completed'    => (int) $r->completed,
            ])
            ->toArray();

        // By priority
        $byPriority = QueueTicket::whereBetween('created_at', [$start, $end])
            ->selectRaw('priority, COUNT(*) as count')
            ->groupBy('priority')
            ->pluck('count', 'priority')
            ->toArray();

        // Avg wait time (issued_at → called_at) in minutes
        $avgWait = QueueTicket::whereBetween('created_at', [$start, $end])
            ->whereNotNull('called_at')
            ->whereNotNull('issued_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, issued_at, called_at)) as avg_wait')
            ->value('avg_wait');

        // By visit type
        $byVisitType = QueueTicket::whereBetween('created_at', [$start, $end])
            ->selectRaw('visit_type, COUNT(*) as count')
            ->groupBy('visit_type')
            ->pluck('count', 'visit_type')
            ->toArray();

        return [
            'total'       => $total,
            'completed'   => $done,
            'no_show'     => $noShow,
            'cancelled'   => $cancelled,
            'avg_wait_min'=> $avgWait ? round((float)$avgWait, 1) : null,
            'byCounter'   => $byCounter,
            'byPriority'  => $byPriority,
            'byVisitType' => $byVisitType,
        ];
    }

    // ── Daily trend for charts ─────────────────────────────

    private function dailyTrend(Carbon $from, Carbon $to): array
    {
        $days = [];
        $cursor = $from->copy()->startOfDay();
        $end    = $to->copy()->endOfDay();

        while ($cursor->lte($end)) {
            $ds = $cursor->copy()->startOfDay();
            $de = $cursor->copy()->endOfDay();

            $days[] = [
                'date'     => $cursor->toDateString(),
                'label'    => $cursor->format('M d'),
                'visits'   => PatientVisit::whereBetween('created_at', [$ds, $de])->count(),
                'revenue'  => (float) Payment::whereBetween('created_at', [$ds, $de])->sum('amount'),
                'patients' => Patient::whereBetween('created_at', [$ds, $de])->count(),
            ];
            $cursor->addDay();
        }

        return $days;
    }

    // ── Helpers ────────────────────────────────────────────

    private function resolveRange(string $preset, ?string $from, ?string $to): array
    {
        return match($preset) {
            'today'      => [Carbon::today(), Carbon::today()],
            'yesterday'  => [Carbon::yesterday(), Carbon::yesterday()],
            'this_week'  => [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()],
            'last_week'  => [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()],
            'this_month' => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
            'last_month' => [Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()],
            'this_year'  => [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()],
            'custom'     => [
                $from ? Carbon::parse($from) : Carbon::now()->startOfMonth(),
                $to   ? Carbon::parse($to)   : Carbon::now(),
            ],
            default      => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
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
