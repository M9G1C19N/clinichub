<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\PatientVisit;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PrescriptionController extends Controller
{
    // ── LIST ─────────────────────────────────────────────
    public function index(Request $request)
    {
        $search = $request->get('search', '');
        $status = $request->get('status', ''); // controlled | regular

        $query = Prescription::with(['patient', 'visit'])
            ->where('doctor_id', Auth::id())
            ->when($search, fn($q) =>
                $q->where('patient_name', 'like', "%{$search}%")
                  ->orWhere('rx_number',   'like', "%{$search}%")
            )
            ->when($status === 'controlled', fn($q) => $q->where('is_controlled', true))
            ->when($status === 'regular',    fn($q) => $q->where('is_controlled', false))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $prescriptions = $query->getCollection()->map(fn($rx) => [
            'id'             => $rx->id,
            'rx_number'      => $rx->rx_number,
            'patient_name'   => $rx->patient_name,
            'patient_code'   => $rx->patient?->patient_code,
            'patient_age_sex'=> $rx->patient_age_sex,
            'visit_type'     => $rx->visit?->visit_type,
            'items'          => $rx->items,
            'items_count'    => count($rx->items ?? []),
            'notes'          => $rx->notes,
            'is_controlled'  => $rx->is_controlled,
            'rx_date'        => $rx->rx_date->format('M d, Y'),
            'patient_id'     => $rx->patient_id,
            'visit_id'       => $rx->patient_visit_id,
        ]);
        $query->setCollection($prescriptions);

        return inertia('Prescriptions/Index', [
            'prescriptions' => $query,
            'search'        => $search,
            'status'        => $status,
            'summary' => [
                'total'      => Prescription::where('doctor_id', Auth::id())->count(),
                'today'      => Prescription::where('doctor_id', Auth::id())
                                    ->whereDate('rx_date', today())->count(),
                'controlled' => Prescription::where('doctor_id', Auth::id())
                                    ->where('is_controlled', true)->count(),
            ],
        ]);
    }

    // ── CREATE FORM ───────────────────────────────────────
    public function create(Request $request)
    {
        $authUser = Auth::user();
        $isNurse  = $authUser->department === 'nursing';

        $visit   = null;
        $patient = null;

        if ($request->filled('visit_id')) {
            $visit   = PatientVisit::with(['patient', 'consultation'])->findOrFail($request->visit_id);
            $patient = $visit->patient;
        } elseif ($request->filled('patient_id')) {
            $patient = Patient::findOrFail($request->patient_id);
        }

        // Pre-fill diagnosis from consultation if available
        $diagnosisHint = '';
        if ($visit?->consultation) {
            $c = $visit->consultation;
            $diagnosisHint = $c->icd10_description
                ?? $c->pe_findings
                ?? $c->soap_assessment
                ?? '';
        }

        // List of doctors for nurse to pick from
        $doctors = User::where('department', 'doctor')
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn($d) => [
                'id'             => $d->id,
                'name'           => $d->name,
                'prc_number'     => $d->prc_number     ?? '',
                'ptr_number'     => $d->ptr_number     ?? '',
                's2_number'      => $d->s2_number      ?? '',
                'specialization' => $d->specialization ?? 'General Practitioner',
            ]);

        // Default doctor: the auth user if doctor, else first in list
        $defaultDoctor = $isNurse
            ? ($doctors->first() ?? ['id' => null, 'name' => '', 'prc_number' => '', 'ptr_number' => '', 's2_number' => '', 'specialization' => ''])
            : [
                'id'             => $authUser->id,
                'name'           => $authUser->name,
                'prc_number'     => $authUser->prc_number     ?? '',
                'ptr_number'     => $authUser->ptr_number     ?? '',
                's2_number'      => $authUser->s2_number      ?? '',
                'specialization' => $authUser->specialization ?? 'General Practitioner',
            ];

        return inertia('Prescriptions/Create', [
            'visit' => $visit ? [
                'id'               => $visit->id,
                'visit_type'       => $visit->visit_type,
                'visit_date'       => $visit->visit_date->format('M d, Y'),
                'employer_company' => $visit->employer_company,
                'diagnosis_hint'   => $diagnosisHint,
            ] : null,
            'patient' => $patient ? [
                'id'           => $patient->id,
                'full_name'    => $patient->full_name,
                'patient_code' => $patient->patient_code,
                'age_sex'      => $patient->age_sex,
                'age'          => $patient->age,
                'sex'          => $patient->sex,
                'birthdate'    => $patient->date_of_birth->format('M d, Y'),
                'address'      => $patient->address ?? '',
            ] : null,
            'doctor'   => $defaultDoctor,
            'doctors'  => $doctors,
            'is_nurse' => $isNurse,
        ]);
    }

    // ── STORE ─────────────────────────────────────────────
    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id'       => ['required', 'exists:patients,id'],
            'patient_visit_id' => ['nullable', 'exists:patient_visits,id'],
            'doctor_id'        => ['nullable', 'exists:users,id'],
            'rx_date'          => ['required', 'date'],
            'items'            => ['required', 'array', 'min:1'],
            'items.*.drug'     => ['required', 'string', 'max:200'],
            'items.*.dosage'   => ['nullable', 'string', 'max:100'],
            'items.*.form'     => ['nullable', 'string', 'max:100'],
            'items.*.quantity' => ['nullable', 'string', 'max:100'],
            'items.*.frequency'=> ['nullable', 'string', 'max:100'],
            'items.*.duration' => ['nullable', 'string', 'max:100'],
            'items.*.instructions' => ['nullable', 'string'],
            'notes'            => ['nullable', 'string'],
            'is_controlled'    => ['boolean'],
        ]);

        // Nurse may submit a selected doctor_id; otherwise use the auth user
        $doctorId = $validated['doctor_id'] ?? Auth::id();
        $doctor   = User::findOrFail($doctorId);
        $patient  = Patient::findOrFail($validated['patient_id']);

        $rx = Prescription::create([
            'patient_id'          => $validated['patient_id'],
            'patient_visit_id'    => $validated['patient_visit_id'] ?? null,
            'doctor_id'           => $doctor->id,
            'rx_date'             => $validated['rx_date'],
            'items'               => $validated['items'],
            'notes'               => $validated['notes'] ?? null,
            'is_controlled'       => $validated['is_controlled'] ?? false,
            // Doctor snapshot
            'doctor_name'         => $doctor->name,
            'doctor_prc'          => $doctor->prc_number ?? '',
            'doctor_ptr'          => $doctor->ptr_number ?? '',
            'doctor_s2'           => $doctor->s2_number  ?? '',
            'doctor_specialization' => $doctor->specialization ?? '',
            // Patient snapshot
            'patient_name'        => $patient->full_name,
            'patient_age_sex'     => $patient->age_sex,
            'patient_address'     => $patient->address ?? '',
        ]);

        return redirect()
            ->route('prescriptions.print', $rx->id)
            ->with('success', "Prescription {$rx->rx_number} saved.");
    }

    // ── PRINT ─────────────────────────────────────────────
    public function print(Prescription $prescription)
    {
        $prescription->load(['doctor.esignature']);
        $rawSig    = $prescription->doctor?->esignature?->signature_path;
        $doctorSig = $this->sigUrl($rawSig);

        return inertia('Prescriptions/Print', [
            'prescription' => [
                'id'             => $prescription->id,
                'rx_number'      => $prescription->rx_number,
                'rx_date'        => $prescription->rx_date->format('M d, Y'),
                'items'          => $prescription->items,
                'notes'          => $prescription->notes,
                'is_controlled'  => $prescription->is_controlled,
                'visit_type'     => $prescription->visit?->visit_type,
            ],
            'patient' => [
                'full_name'    => $prescription->patient_name,
                'age_sex'      => $prescription->patient_age_sex,
                'address'      => $prescription->patient_address,
                'patient_code' => $prescription->patient?->patient_code,
            ],
            'doctor' => [
                'name'           => $prescription->doctor_name,
                'prc_number'     => $prescription->doctor_prc,
                'ptr_number'     => $prescription->doctor_ptr,
                's2_number'      => $prescription->doctor_s2,
                'specialization' => $prescription->doctor_specialization,
                'signature_url'   => $doctorSig,
                'signature_scale' => $prescription->doctor?->esignature?->signature_scale ?? 1.0,
            ],
        ]);
    }

    // ── CANCEL ────────────────────────────────────────────
    public function destroy(Prescription $prescription)
    {
        $prescription->delete();
        return back()->with('success', 'Prescription removed.');
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
