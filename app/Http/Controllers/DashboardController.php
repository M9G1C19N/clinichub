<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\DrugTestRequest;
use App\Models\ImagingRequest;
use App\Models\Invoice;
use App\Models\LaboratoryRequest;
use App\Models\LaboratoryResult;
use App\Models\Patient;
use App\Models\PatientVisit;
use App\Models\PatientVital;
use App\Models\Payment;
use App\Models\Prescription;
use App\Models\QueueRoomAssignment;
use App\Models\QueueTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user()->load('roles');
        $role = $user->getRoleNames()->first();

        $dashboardMap = [
            'admin'           => 'Dashboard/Admin',
            'receptionist'    => 'Dashboard/Receptionist',
            'nurse'           => 'Dashboard/Nurse',
            'doctor'          => 'Dashboard/Doctor',
            'lab_technician'  => 'Dashboard/Laboratory',
            'xray_tech'       => 'Dashboard/XRay',
            'drug_test_staff' => 'Dashboard/DrugTest',
            'billing'         => 'Dashboard/Billing',
        ];

        $component = $dashboardMap[$role] ?? 'Dashboard/Admin';
        $data      = $this->getDataForRole($role);

        return inertia($component, array_merge(['user' => $user, 'role' => $role], $data));
    }

    private function getDataForRole(string $role): array
    {
        return match ($role) {
            'admin'           => $this->adminData(),
            'receptionist'    => $this->receptionistData(),
            'nurse'           => $this->nurseData(),
            'doctor'          => $this->doctorData(),
            'lab_technician'  => $this->labData(),
            'xray_tech'       => $this->xrayData(),
            'drug_test_staff' => $this->drugTestData(),
            'billing'         => $this->billingData(),
            default           => $this->adminData(),
        };
    }

    // ── ADMIN ─────────────────────────────────────────────────────────────

    private function adminData(): array
    {
        $todayVisits  = PatientVisit::whereDate('visit_date', today())->count();
        $todayRevenue = Payment::whereDate('created_at', today())->sum('amount');
        $pendingPay   = Invoice::whereIn('status', ['unpaid', 'partial'])->count();
        $activeQueue  = QueueRoomAssignment::today()
            ->whereIn('status', ['waiting', 'calling', 'serving'])->count();

        // Yesterday comparisons
        $yesterdayVisits  = PatientVisit::whereDate('visit_date', today()->subDay())->count();
        $yesterdayRevenue = Payment::whereDate('created_at', today()->subDay())->sum('amount');

        // Last 7 days visit trend
        $visitTrend = PatientVisit::selectRaw('DATE(visit_date) as date, COUNT(*) as count')
            ->whereBetween('visit_date', [now()->subDays(6)->startOfDay(), now()->endOfDay()])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($r) => [
                'date'  => \Carbon\Carbon::parse($r->date)->format('M d'),
                'count' => (int) $r->count,
            ]);

        // Visit type breakdown today
        $visitTypeBreakdown = PatientVisit::selectRaw('visit_type, COUNT(*) as count')
            ->whereDate('visit_date', today())
            ->groupBy('visit_type')
            ->pluck('count', 'visit_type')
            ->toArray();

        // Revenue last 6 months
        $revenueTrend = Payment::selectRaw(
            'YEAR(created_at) as year, MONTH(created_at) as month, SUM(amount) as total'
        )
            ->where('created_at', '>=', now()->subMonths(5)->startOfMonth())
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get()
            ->map(fn ($r) => [
                'month' => \Carbon\Carbon::createFromDate($r->year, $r->month, 1)->format('M Y'),
                'total' => (float) $r->total,
            ]);

        // Pending invoices
        $pendingInvoices = Invoice::whereIn('status', ['unpaid', 'partial'])
            ->with(['visit.patient'])
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn ($inv) => [
                'id'             => $inv->id,
                'patient_name'   => $inv->visit?->patient?->full_name ?? '—',
                'invoice_number' => $inv->invoice_number,
                'total'          => $inv->total_amount,
                'balance'        => $inv->balance,
                'status'         => $inv->status,
                'visit_date'     => $inv->visit?->visit_date?->format('M d'),
                'visit_type'     => $inv->visit?->visit_type,
            ]);

        // Top services today
        $topServices = DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->join('patient_visits', 'invoices.patient_visit_id', '=', 'patient_visits.id')
            ->whereDate('patient_visits.visit_date', today())
            ->selectRaw('invoice_items.service_name, COUNT(*) as count, SUM(invoice_items.subtotal) as revenue')
            ->groupBy('invoice_items.service_name')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        // Recent activity
        $recentActivity = DB::table('laboratory_requests')
            ->join('users', 'laboratory_requests.released_by', '=', 'users.id')
            ->join('patients', 'laboratory_requests.patient_id', '=', 'patients.id')
            ->whereDate('laboratory_requests.released_at', today())
            ->selectRaw("users.name as staff, patients.first_name, patients.last_name,
                'Lab Released' as action, laboratory_requests.released_at as time")
            ->orderByDesc('time')
            ->limit(5)
            ->get()
            ->map(fn ($r) => [
                'staff'  => $r->staff,
                'action' => $r->action,
                'patient'=> $r->last_name . ', ' . $r->first_name,
                'time'   => \Carbon\Carbon::parse($r->time)->format('h:i A'),
            ]);

        // Stats breakdown
        $roomActivity = [];
        foreach (['laboratory', 'xray_utz', 'drug_test', 'interview_room'] as $room) {
            $roomActivity[$room] = [
                'waiting'   => QueueRoomAssignment::today()->forRoom($room)->where('status', 'waiting')->count(),
                'serving'   => QueueRoomAssignment::today()->forRoom($room)->where('status', 'serving')->count(),
                'completed' => QueueRoomAssignment::today()->forRoom($room)->where('status', 'completed')->count(),
            ];
        }

        // Appointments
        $apptPending   = Appointment::where('status', 'pending')->count();
        $apptToday     = Appointment::whereDate('preferred_date', today())->whereIn('status', ['confirmed', 'pending'])->count();
        $apptThisWeek  = Appointment::whereBetween('preferred_date', [now()->startOfWeek(), now()->endOfWeek()])->count();
        $recentAppointments = Appointment::where('status', 'pending')
            ->orderByDesc('created_at')->limit(5)->get()
            ->map(fn($a) => [
                'id'             => $a->id,
                'appointment_number' => $a->appointment_number,
                'patient_name'   => $a->patient_name,
                'service_label'  => $a->service_label,
                'preferred_date' => $a->preferred_date->format('M d, Y'),
                'preferred_time' => $a->preferred_time_label,
                'created_at'     => $a->created_at->diffForHumans(),
            ]);

        // Prescriptions
        $prescriptionsToday = Prescription::whereDate('rx_date', today())->count();

        return [
            'stats' => [
                'today_visits'         => $todayVisits,
                'today_revenue'        => (float) $todayRevenue,
                'pending_payment'      => $pendingPay,
                'active_queue'         => $activeQueue,
                'visits_trend'         => $yesterdayVisits > 0 ? round((($todayVisits - $yesterdayVisits) / $yesterdayVisits) * 100) : 0,
                'revenue_trend'        => $yesterdayRevenue > 0 ? round((($todayRevenue - $yesterdayRevenue) / $yesterdayRevenue) * 100) : 0,
                'appointments_pending' => $apptPending,
                'appointments_today'   => $apptToday,
                'appointments_week'    => $apptThisWeek,
                'prescriptions_today'  => $prescriptionsToday,
            ],
            'visitTrend'           => $visitTrend,
            'revenueTrend'         => $revenueTrend,
            'visitTypeBreakdown'   => $visitTypeBreakdown,
            'pendingInvoices'      => $pendingInvoices,
            'topServices'          => $topServices,
            'recentActivity'       => $recentActivity,
            'roomActivity'         => $roomActivity,
            'recentAppointments'   => $recentAppointments,
            'totalPatients'        => Patient::count(),
            'newPatientsToday'     => Patient::whereDate('created_at', today())->count(),
        ];
    }

    // ── RECEPTIONIST ─────────────────────────────────────────────────────

    private function receptionistData(): array
    {
        $todayVisits = PatientVisit::with([
            'patient',
            'invoice',
            'labRequest',
            'imagingRequest',
            'drugTestRequest',
            'consultation',
        ])
            ->whereDate('visit_date', today())
            ->latest()
            ->get()
            ->map(fn ($v) => [
                'id'             => $v->id,
                'patient_name'   => $v->patient->full_name,
                'patient_code'   => $v->patient->patient_code,
                'visit_type'     => $v->visit_type,
                'case_number'    => $v->case_number,
                'employer'       => $v->employer_company,
                'invoice_id'     => $v->invoice?->id,
                'invoice_status' => $v->invoice?->status ?? 'none',
                'total_amount'   => (float) ($v->invoice?->total_amount ?? 0),
                'balance'        => (float) ($v->invoice?->balance ?? 0),
                'lab_status'     => $v->labRequest?->status ?? 'none',
                'xray_status'    => $v->imagingRequest?->status ?? 'none',
                'drug_status'    => $v->drugTestRequest?->status ?? 'none',
                'doctor_status'  => $v->consultation ? ($v->consultation->is_finalized ? 'finalized' : 'in_progress') : 'none',
                'registered_at'  => $v->created_at->format('h:i A'),
                'is_field_visit' => $v->is_field_visit ?? false,
                'minutes_ago'    => $v->created_at->diffInMinutes(now()),
            ]);

        $totalBalance = $todayVisits->sum('balance');
        $unpaidCount  = $todayVisits->where('invoice_status', 'unpaid')->count();
        $partialCount = $todayVisits->where('invoice_status', 'partial')->count();

        // Active queue per room
        $queueStats = [];
        foreach (['laboratory', 'xray_utz', 'drug_test', 'interview_room'] as $room) {
            $queueStats[$room] = QueueRoomAssignment::today()
                ->forRoom($room)
                ->whereIn('status', ['waiting', 'calling', 'serving'])
                ->count();
        }

        $pendingAppointments = Appointment::where('status', 'pending')
            ->orderByDesc('created_at')->limit(8)->get()
            ->map(fn($a) => [
                'id'             => $a->id,
                'appointment_number' => $a->appointment_number,
                'patient_name'   => $a->patient_name,
                'patient_phone'  => $a->patient_phone,
                'service_label'  => $a->service_label,
                'preferred_date' => $a->preferred_date->format('M d, Y'),
                'preferred_time' => $a->preferred_time_label,
                'created_at'     => $a->created_at->diffForHumans(),
            ]);

        return [
            'stats' => [
                'today_visits'         => $todayVisits->count(),
                'pending_payment'      => $unpaidCount + $partialCount,
                'pending_balance'      => $totalBalance,
                'completed'            => $todayVisits->where('invoice_status', 'paid')->count(),
                'active_queue'         => array_sum($queueStats),
                'appointments_pending' => Appointment::where('status', 'pending')->count(),
                'appointments_today'   => Appointment::whereDate('preferred_date', today())->whereIn('status', ['confirmed','pending'])->count(),
            ],
            'todayVisits'         => $todayVisits,
            'queueStats'          => $queueStats,
            'fieldVisitsPending'  => PatientVisit::where('is_field_visit', true)
                ->whereNull('case_number')->count(),
            'pendingAppointments' => $pendingAppointments,
            'recentPayments'      => Payment::with('invoice.visit.patient')
                ->whereDate('created_at', today())
                ->latest()->limit(5)->get()
                ->map(fn ($p) => [
                    'amount'       => (float) $p->amount,
                    'method'       => $p->method,
                    'patient_name' => $p->invoice?->visit?->patient?->full_name ?? '—',
                    'time'         => $p->created_at->format('h:i A'),
                ]),
        ];
    }

    // ── NURSE ─────────────────────────────────────────────────────────────

    private function nurseData(): array
    {
        $queue = QueueRoomAssignment::with(['ticket.patient', 'ticket.visit.vitals'])
            ->today()
            ->forRoom('interview_room')
            ->whereNotIn('status', ['no_show', 'skipped', 'cancelled'])
            ->orderByRaw("FIELD(status, 'serving','calling','waiting','completed')")
            ->get()
            ->map(fn ($a) => [
                'id'           => $a->id,
                'queue_number' => $a->queue_number,
                'status'       => $a->status,
                'priority'     => $a->ticket?->priority ?? 'regular',
                'patient_name' => $a->ticket?->patient?->full_name ?? '—',
                'patient_code' => $a->ticket?->patient?->patient_code ?? '—',
                'age_sex'      => $a->ticket?->patient?->age_sex ?? '—',
                'sex'          => $a->ticket?->patient?->sex ?? 'male',
                'visit_id'     => $a->ticket?->visit?->id,
                'visit_type'   => $a->ticket?->visit?->visit_type,
                'employer'     => $a->ticket?->visit?->employer_company,
                'has_vitals'   => $a->ticket?->visit?->vitals !== null,
                'called_at'    => $a->called_at?->format('h:i A'),
                'wait_mins'    => $a->created_at ? $a->created_at->diffInMinutes(now()) : 0,
            ]);

        $vitalsTaken = PatientVital::whereHas('visit', fn ($q) =>
            $q->whereDate('visit_date', today())
        )->count();

        $visitTypeCounts = PatientVisit::selectRaw('visit_type, COUNT(*) as count')
            ->whereDate('visit_date', today())
            ->whereIn('visit_type', ['pre_employment', 'annual_pe', 'exit_pe', 'opd', 'follow_up'])
            ->groupBy('visit_type')
            ->pluck('count', 'visit_type')
            ->toArray();

        // Pending from previous days
        $backlog = PatientVisit::with('patient')
            ->whereDoesntHave('vitals')
            ->whereDate('visit_date', '<', today())
            ->where('status', '!=', 'cancelled')
            ->whereIn('visit_type', ['opd', 'pre_employment', 'annual_pe', 'exit_pe', 'follow_up'])
            ->latest('visit_date')
            ->limit(5)
            ->get()
            ->map(fn ($v) => [
                'id'           => $v->id,
                'patient_name' => $v->patient->full_name,
                'patient_code' => $v->patient->patient_code,
                'visit_type'   => $v->visit_type,
                'visit_date'   => $v->visit_date->format('M d, Y'),
                'days_ago'     => $v->visit_date->diffInDays(today()),
            ]);

        $prescriptionsToday = Prescription::whereDate('rx_date', today())->count();
        $labPendingToday    = LaboratoryRequest::whereIn('status', ['pending', 'processing'])
            ->whereHas('visit', fn($q) => $q->whereDate('visit_date', today()))->count();

        return [
            'stats' => [
                'in_queue'            => $queue->count(),
                'vitals_taken'        => $vitalsTaken,
                'pending'             => $queue->where('has_vitals', false)->count(),
                'pe_today'            => ($visitTypeCounts['pre_employment'] ?? 0) +
                                         ($visitTypeCounts['annual_pe'] ?? 0) +
                                         ($visitTypeCounts['exit_pe'] ?? 0),
                'prescriptions_today' => $prescriptionsToday,
                'lab_pending_today'   => $labPendingToday,
            ],
            'queue'           => $queue,
            'visitTypeCounts' => $visitTypeCounts,
            'backlog'         => $backlog,
        ];
    }

    // ── DOCTOR ────────────────────────────────────────────────────────────

    private function doctorData(): array
    {
        // Patients ready for review (all services released)
        $pending = PatientVisit::with([
            'patient',
            'labRequest',
            'imagingRequest',
            'drugTestRequest',
            'vitals',
            'invoice.items',
        ])
            ->whereDate('visit_date', today())
            ->whereDoesntHave('consultation', fn ($q) => $q->where('is_finalized', true))
            ->where('status', '!=', 'cancelled')
            ->whereIn('visit_type', ['opd', 'pre_employment', 'annual_pe', 'exit_pe', 'follow_up'])
            ->latest('visit_date')
            ->get()
            ->map(fn ($v) => [
                'id'             => $v->id,
                'patient_name'   => $v->patient->full_name,
                'patient_code'   => $v->patient->patient_code,
                'age_sex'        => $v->patient->age_sex,
                'sex'            => $v->patient->sex,
                'visit_type'     => $v->visit_type,
                'employer'       => $v->employer_company,
                'has_vitals'     => $v->vitals !== null,
                'has_draft'      => $v->consultation !== null,
                'lab_status'     => $v->labRequest?->status ?? 'none',
                'xray_status'    => $v->imagingRequest?->status ?? 'none',
                'drug_status'    => $v->drugTestRequest?->status ?? 'none',
                'abnormal_count' => $v->labRequest?->results()
                    ->where('is_abnormal', true)->count() ?? 0,
                'all_results_in' => $this->allResultsReleased($v),
                'services'       => collect($v->invoice?->items ?? [])
                    ->map(fn ($i) => $i->service_code)->toArray(),
                'registered_at'  => $v->created_at->format('h:i A'),
                'wait_mins'      => $v->created_at->diffInMinutes(now()),
            ]);

        // PE classification summary
        $classificationSummary = Consultation::selectRaw('pe_classification, COUNT(*) as count')
            ->whereHas('visit', fn ($q) => $q->whereDate('visit_date', today()))
            ->whereNotNull('pe_classification')
            ->where('is_finalized', true)
            ->groupBy('pe_classification')
            ->pluck('count', 'pe_classification');

        // Completed today
        $completedToday = Consultation::with(['patient', 'visit'])
            ->where('is_finalized', true)
            ->whereDate('finalized_at', today())
            ->latest('finalized_at')
            ->limit(8)
            ->get()
            ->map(fn ($c) => [
                'id'                => $c->id,
                'patient_name'      => $c->patient->full_name,
                'visit_type'        => $c->visit_type,
                'pe_classification' => $c->pe_classification,
                'icd10_code'        => $c->icd10_code,
                'icd10_description' => $c->icd10_description,
                'finalized_at'      => $c->finalized_at?->format('h:i A'),
                'visit_id'          => $c->patient_visit_id,
            ]);

        $abnormalToday = LaboratoryResult::where('is_abnormal', true)
            ->whereHas('request.visit', fn ($q) => $q->whereDate('visit_date', today()))
            ->count();

        $myPrescriptionsToday = Prescription::where('doctor_id', Auth::id())
            ->whereDate('rx_date', today())->count();
        $totalPrescriptionsToday = Prescription::whereDate('rx_date', today())->count();

        return [
            'stats' => [
                'ready_for_review'       => $pending->where('all_results_in', true)->count(),
                'pending_total'          => $pending->count(),
                'completed_today'        => Consultation::where('is_finalized', true)
                    ->whereDate('finalized_at', today())->count(),
                'abnormal_today'         => $abnormalToday,
                'pe_done_today'          => Consultation::where('is_finalized', true)
                    ->whereNotNull('pe_classification')
                    ->whereDate('finalized_at', today())->count(),
                'my_prescriptions_today' => $myPrescriptionsToday,
                'all_prescriptions_today'=> $totalPrescriptionsToday,
            ],
            'pending'               => $pending,
            'completedToday'        => $completedToday,
            'classificationSummary' => $classificationSummary,
        ];
    }

    private function allResultsReleased(PatientVisit $visit): bool
    {
        $services = $visit->services_selected ?? [];

        $labServices = ['CBC', 'UA', 'FECALYSIS', 'BLOODTYPING', 'FBS', 'RBS', 'BUN',
            'CREATININE', 'URICACID', 'CHOLESTEROL', 'TRIGLYCERIDES', 'HDLLDL',
            'SGOT', 'SGPT', 'HBSAG', 'VDRL', 'PREGNANCY', 'DENGUE', 'THYROID'];
        $xrayServices = ['CXRPA', 'UTZ', 'UTZ_ABDOMEN', 'UTZ_KUB', 'UTZ_PELVIS', 'ECG'];
        $drugServices = ['DRUGTEST', 'DRUGTEST5', 'MET', 'THC'];

        $needsLab  = !empty(array_intersect($services, $labServices));
        $needsXray = !empty(array_intersect($services, $xrayServices));
        $needsDrug = !empty(array_intersect($services, $drugServices));

        if ($needsLab && $visit->labRequest?->status !== 'released') return false;
        if ($needsXray && $visit->imagingRequest?->status !== 'released') return false;
        if ($needsDrug && $visit->drugTestRequest?->status !== 'released') return false;

        return true;
    }

    // ── LABORATORY ───────────────────────────────────────────────────────

    private function labData(): array
    {
        $queue = QueueRoomAssignment::with(['ticket.patient', 'ticket.visit.labRequest', 'ticket.visit.invoice.items'])
            ->today()
            ->forRoom('laboratory')
            ->whereNotIn('status', ['no_show', 'skipped', 'cancelled'])
            ->orderByRaw("FIELD(status, 'serving','calling','waiting','completed')")
            ->get()
            ->map(fn ($a) => [
                'id'             => $a->id,
                'queue_number'   => $a->queue_number,
                'status'         => $a->status,
                'priority'       => $a->ticket?->priority ?? 'regular',
                'patient_name'   => $a->ticket?->patient?->full_name ?? '—',
                'patient_code'   => $a->ticket?->patient?->patient_code ?? '—',
                'age_sex'        => $a->ticket?->patient?->age_sex ?? '—',
                'visit_id'       => $a->ticket?->visit?->id,
                'visit_type'     => $a->ticket?->visit?->visit_type,
                'employer'       => $a->ticket?->visit?->employer_company,
                'lab_status'     => $a->ticket?->visit?->labRequest?->status ?? 'pending',
                'lab_request_id' => $a->ticket?->visit?->labRequest?->id,
                'services'       => collect($a->ticket?->visit?->invoice?->items ?? [])
                    ->filter(fn ($i) => in_array($i->service_code, ['CBC', 'UA', 'FECALYSIS',
                        'BLOODTYPING', 'FBS', 'RBS', 'BUN', 'CREATININE', 'URICACID',
                        'CHOLESTEROL', 'TRIGLYCERIDES', 'HDLLDL', 'SGOT', 'SGPT',
                        'HBSAG', 'VDRL', 'PREGNANCY', 'DENGUE', 'THYROID']))
                    ->map(fn ($i) => $i->service_code)
                    ->values()
                    ->toArray(),
                'wait_mins' => $a->created_at ? $a->created_at->diffInMinutes(now()) : 0,
            ]);

        // Test category volume today
        $testVolume = DB::table('laboratory_results')
            ->join('lab_tests', 'laboratory_results.lab_test_id', '=', 'lab_tests.id')
            ->join('laboratory_requests', 'laboratory_results.lab_request_id', '=', 'laboratory_requests.id')
            ->whereDate('laboratory_requests.created_at', today())
            ->selectRaw('lab_tests.category, COUNT(*) as count')
            ->groupBy('lab_tests.category')
            ->pluck('count', 'category')
            ->toArray();

        // Recent releases
        $recentReleases = LaboratoryRequest::with('patient')
            ->where('status', 'released')
            ->whereDate('released_at', today())
            ->latest('released_at')
            ->limit(5)
            ->get()
            ->map(fn ($r) => [
                'patient_name'   => $r->patient->full_name,
                'request_number' => $r->request_number,
                'released_at'    => $r->released_at?->format('h:i A'),
                'has_abnormal'   => $r->results()->where('is_abnormal', true)->exists(),
            ]);

        // Pending from previous days
        $backlog = LaboratoryRequest::with(['patient', 'visit'])
            ->whereIn('status', ['pending', 'processing'])
            ->whereDate('created_at', '<', today())
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($r) => [
                'id'             => $r->id,
                'patient_name'   => $r->patient->full_name,
                'request_number' => $r->request_number,
                'visit_id'       => $r->patient_visit_id,
                'visit_type'     => $r->visit?->visit_type,
                'date'           => $r->created_at->format('M d'),
            ]);

        return [
            'stats' => [
                'in_queue'       => $queue->count(),
                'pending_entry'  => LaboratoryRequest::whereIn('status', ['pending', 'processing'])
                    ->whereHas('visit', fn ($q) => $q->whereDate('visit_date', today()))
                    ->count(),
                'released_today' => LaboratoryRequest::where('status', 'released')
                    ->whereDate('released_at', today())->count(),
                'abnormal_today' => LaboratoryResult::where('is_abnormal', true)
                    ->whereHas('request.visit', fn ($q) => $q->whereDate('visit_date', today()))
                    ->count(),
            ],
            'queue'          => $queue,
            'testVolume'     => $testVolume,
            'recentReleases' => $recentReleases,
            'backlog'        => $backlog,
        ];
    }

    // ── X-RAY ─────────────────────────────────────────────────────────────

    private function xrayData(): array
    {
        $queue = QueueRoomAssignment::with(['ticket.patient', 'ticket.visit.imagingRequest', 'ticket.visit.invoice.items'])
            ->today()
            ->forRoom('xray_utz')
            ->whereNotIn('status', ['no_show', 'skipped', 'cancelled'])
            ->orderByRaw("FIELD(status, 'serving','calling','waiting','completed')")
            ->get()
            ->map(fn ($a) => [
                'id'              => $a->id,
                'queue_number'    => $a->queue_number,
                'status'          => $a->status,
                'priority'        => $a->ticket?->priority ?? 'regular',
                'patient_name'    => $a->ticket?->patient?->full_name ?? '—',
                'patient_code'    => $a->ticket?->patient?->patient_code ?? '—',
                'age_sex'         => $a->ticket?->patient?->age_sex ?? '—',
                'visit_id'        => $a->ticket?->visit?->id,
                'visit_type'      => $a->ticket?->visit?->visit_type,
                'employer'        => $a->ticket?->visit?->employer_company,
                'imaging_status'  => $a->ticket?->visit?->imagingRequest?->status ?? 'pending',
                'imaging_type'    => $a->ticket?->visit?->imagingRequest?->imaging_type_label ?? 'Chest X-Ray PA',
                'is_provisional'  => $a->ticket?->visit?->imagingRequest?->is_provisional ?? false,
                'wait_mins'       => $a->created_at ? $a->created_at->diffInMinutes(now()) : 0,
            ]);

        // Exam type breakdown
        $examBreakdown = ImagingRequest::selectRaw('imaging_type, COUNT(*) as count')
            ->whereDate('created_at', today())
            ->groupBy('imaging_type')
            ->pluck('count', 'imaging_type')
            ->toArray();

        // Recent releases
        $recentReleases = ImagingRequest::with('patient')
            ->where('status', 'released')
            ->whereDate('released_at', today())
            ->latest('released_at')
            ->limit(5)
            ->get()
            ->map(fn ($r) => [
                'patient_name'   => $r->patient->full_name,
                'request_number' => $r->request_number,
                'imaging_type'   => $r->imaging_type_label,
                'released_at'    => $r->released_at?->format('h:i A'),
                'is_provisional' => $r->is_provisional,
            ]);

        return [
            'stats' => [
                'in_queue'            => $queue->count(),
                'pending'             => ImagingRequest::whereIn('status', ['pending', 'processing'])
                    ->whereHas('visit', fn ($q) => $q->whereDate('visit_date', today()))
                    ->count(),
                'released_today'      => ImagingRequest::where('status', 'released')
                    ->whereDate('released_at', today())->count(),
                'provisional_pending' => ImagingRequest::where('is_provisional', true)
                    ->where('status', '!=', 'released')->count(),
            ],
            'queue'          => $queue,
            'examBreakdown'  => $examBreakdown,
            'recentReleases' => $recentReleases,
        ];
    }

    // ── DRUG TEST ─────────────────────────────────────────────────────────

    private function drugTestData(): array
    {
        $queue = QueueRoomAssignment::with(['ticket.patient', 'ticket.visit.drugTestRequest'])
            ->today()
            ->forRoom('drug_test')
            ->whereNotIn('status', ['no_show', 'skipped', 'cancelled'])
            ->orderByRaw("FIELD(status, 'serving','calling','waiting','completed')")
            ->get()
            ->map(fn ($a) => [
                'id'           => $a->id,
                'queue_number' => $a->queue_number,
                'status'       => $a->status,
                'priority'     => $a->ticket?->priority ?? 'regular',
                'patient_name' => $a->ticket?->patient?->full_name ?? '—',
                'patient_code' => $a->ticket?->patient?->patient_code ?? '—',
                'age_sex'      => $a->ticket?->patient?->age_sex ?? '—',
                'visit_id'     => $a->ticket?->visit?->id,
                'employer'     => $a->ticket?->visit?->employer_company ?? '—',
                'drug_status'  => $a->ticket?->visit?->drugTestRequest?->status ?? 'pending',
                'result'       => $a->ticket?->visit?->drugTestRequest?->result,
                'drugs_label'  => $a->ticket?->visit?->drugTestRequest?->drugs_label ?? 'THC & MET',
                'code_number'  => $a->ticket?->visit?->drugTestRequest?->code_number,
                'wait_mins'    => $a->created_at ? $a->created_at->diffInMinutes(now()) : 0,
            ]);

        // Result distribution today
        $resultDistribution = DrugTestRequest::selectRaw('result, COUNT(*) as count')
            ->whereDate('updated_at', today())
            ->whereNotNull('result')
            ->groupBy('result')
            ->pluck('count', 'result')
            ->toArray();

        // Specimen stats
        $tempInRange = DrugTestRequest::whereDate('created_at', today())
            ->where('temp_in_range', true)->count();
        $totalSpecimens = DrugTestRequest::whereDate('created_at', today())
            ->whereNotNull('specimen_type')->count();

        return [
            'stats' => [
                'in_queue'        => $queue->count(),
                'collected'       => DrugTestRequest::whereIn('status', ['processing', 'released'])
                    ->whereHas('visit', fn ($q) => $q->whereDate('visit_date', today()))
                    ->count(),
                'released_today'  => DrugTestRequest::where('status', 'released')
                    ->whereDate('released_at', today())->count(),
                'positive_today'  => DrugTestRequest::where('result', 'positive')
                    ->whereDate('updated_at', today())->count(),
            ],
            'queue'               => $queue,
            'resultDistribution'  => $resultDistribution,
            'tempInRangeCount'    => $tempInRange,
            'totalSpecimens'      => $totalSpecimens,
        ];
    }

    // ── BILLING ───────────────────────────────────────────────────────────

    private function billingData(): array
    {
        $today = today();

        $totalToday     = Payment::whereDate('created_at', $today)->sum('amount');
        $billedToday    = Invoice::whereDate('created_at', $today)->sum('total_amount');
        $unpaidCount    = Invoice::where('status', 'unpaid')->count();
        $unpaidBalance  = Invoice::where('status', 'unpaid')->sum('balance');
        $partialCount   = Invoice::where('status', 'partial')->count();
        $partialBalance = Invoice::where('status', 'partial')->sum('balance');
        $paidToday      = Invoice::where('status', 'paid')->whereDate('paid_at', $today)->count();

        // Revenue last 7 days
        $revenueTrend = Payment::selectRaw('DATE(created_at) as date, SUM(amount) as total')
            ->whereBetween('created_at', [now()->subDays(6)->startOfDay(), now()->endOfDay()])
            ->groupBy('date')->orderBy('date')
            ->get()
            ->map(fn($r) => [
                'date'  => \Carbon\Carbon::parse($r->date)->format('M d'),
                'total' => (float) $r->total,
            ]);

        // By payment method today
        $byMethod = Payment::whereDate('created_at', $today)
            ->selectRaw('method, SUM(amount) as total')
            ->groupBy('method')
            ->pluck('total', 'method')
            ->toArray();

        // Recent unpaid/partial invoices
        $pendingInvoices = Invoice::with('patient')
            ->whereIn('status', ['unpaid', 'partial'])
            ->latest()->limit(10)
            ->get()
            ->map(fn($inv) => [
                'id'             => $inv->id,
                'invoice_number' => $inv->invoice_number,
                'patient_name'   => $inv->patient->full_name,
                'patient_code'   => $inv->patient->patient_code,
                'total_amount'   => (float) $inv->total_amount,
                'balance'        => (float) $inv->balance,
                'status'         => $inv->status,
                'created_at'     => $inv->created_at->format('M d, Y'),
            ]);

        // Recent payments
        $recentPayments = Payment::with(['invoice.patient'])
            ->whereDate('created_at', $today)
            ->latest()->limit(10)
            ->get()
            ->map(fn($p) => [
                'id'             => $p->id,
                'amount'         => (float) $p->amount,
                'method'         => $p->method,
                'invoice_number' => $p->invoice->invoice_number,
                'patient_name'   => $p->invoice->patient->full_name,
                'created_at'     => $p->created_at->format('h:i A'),
            ]);

        $apptPending     = Appointment::where('status', 'pending')->count();
        $apptConfToday   = Appointment::whereDate('preferred_date', today())->where('status', 'confirmed')->count();
        $visitTypeRevenue = Invoice::join('patient_visits', 'invoices.patient_visit_id', '=', 'patient_visits.id')
            ->whereDate('invoices.created_at', $today)
            ->where('invoices.status', '!=', 'cancelled')
            ->selectRaw('patient_visits.visit_type, SUM(invoices.total_amount) as total')
            ->groupBy('patient_visits.visit_type')
            ->orderByDesc('total')
            ->pluck('total', 'visit_type')
            ->toArray();

        return [
            'stats' => [
                'collected_today'      => (float) $totalToday,
                'billed_today'         => (float) $billedToday,
                'paid_today'           => $paidToday,
                'unpaid_count'         => $unpaidCount,
                'unpaid_balance'       => (float) $unpaidBalance,
                'partial_count'        => $partialCount,
                'partial_balance'      => (float) $partialBalance,
                'appointments_pending' => $apptPending,
                'appointments_today'   => $apptConfToday,
            ],
            'revenueTrend'     => $revenueTrend,
            'byMethod'         => $byMethod,
            'pendingInvoices'  => $pendingInvoices,
            'recentPayments'   => $recentPayments,
            'visitTypeRevenue' => $visitTypeRevenue,
        ];
    }
}
