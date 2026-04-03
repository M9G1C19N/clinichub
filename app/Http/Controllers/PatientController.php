<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class PatientController extends Controller
{
        use AuthorizesRequests;
     // ── LIST ──────────────────────────────────────────────

    public function index(Request $request)
    {
        $this->authorize('patients.view');

        $query = Patient::with('registeredBy')
            ->withCount('visits');

        // Search
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Filter by sex
        if ($request->filled('sex')) {
            $query->where('sex', $request->sex);
        }

        // Filter by visit type
        if ($request->filled('visit_type')) {
            $query->where('visit_type_default', $request->visit_type);
        }

        // Filter active/inactive
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $patients = $query
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(15)
            ->withQueryString();

        return inertia('Patients/Index', [
            'patients' => $patients->through(fn ($p) => [
                'id'               => $p->id,
                'patient_code'     => $p->patient_code,
                'full_name'        => $p->full_name,
                'first_name'       => $p->first_name,
                'last_name'        => $p->last_name,
                'age_sex'          => $p->age_sex,
                'sex'              => $p->sex,
                'contact_number'   => $p->contact_number,
                'visit_type_default' => $p->visit_type_default,
                'is_active'        => $p->is_active,
                'visits_count'     => $p->visits_count,
                'photo_path'       => $p->photo_path,
                'created_at'       => $p->created_at->format('M d, Y'),
            ]),
            'filters' => $request->only(['search', 'sex', 'visit_type', 'status']),
            'total'   => Patient::count(),
        ]);
    }

    // ── CREATE FORM ───────────────────────────────────────

    public function create()
    {
        $this->authorize('patients.create');

        return inertia('Patients/Create');
    }

    // ── STORE ─────────────────────────────────────────────

    public function store(Request $request)
    {
        $this->authorize('patients.create');

        $validated = $request->validate([
            'first_name'               => ['required', 'string', 'max:100'],
            'last_name'                => ['required', 'string', 'max:100'],
            'middle_name'              => ['nullable', 'string', 'max:100'],
            'date_of_birth'            => ['required', 'date', 'before:today'],
            'sex'                      => ['required', 'in:male,female'],
            'civil_status'             => ['required', 'in:single,married,widowed,separated'],
            'contact_number'           => ['nullable', 'string', 'max:15'],
            'email'                    => ['nullable', 'email', 'max:150'],
            'address'                  => ['nullable', 'string'],
            'philhealth_number'        => ['nullable', 'string', 'max:20'],
            'blood_type'               => ['nullable', 'in:A+,A-,B+,B-,O+,O-,AB+,AB-'],
            'occupation'               => ['nullable', 'string', 'max:100'],
            'emergency_contact_name'   => ['nullable', 'string', 'max:150'],
            'emergency_contact_number' => ['nullable', 'string', 'max:15'],
            'visit_type_default' => ['required', 'in:opd,pre_employment,annual_pe,exit_pe,follow_up,lab_only'],
            'photo'                    => ['nullable', 'image', 'max:2048'],
        ]);

        // Handle photo upload
        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')
                ->store('patients/photos', 'public');
        }

        $patient = Patient::create([
            ...$validated,
            'photo_path' => $photoPath,
            'created_by' => Auth::id(),
        ]);

        return redirect()
            ->route('patients.show', $patient)
            ->with('success', "Patient {$patient->full_name} registered successfully! Code: {$patient->patient_code}");
    }

    // ── SHOW ──────────────────────────────────────────────

    public function show(Patient $patient)
    {
        $this->authorize('patients.view');

        $patient->load(['registeredBy']);

        // All visits with full related data
        $visits = $patient->visits()
            ->with([
                'vitals',
                'consultation',
                'labRequest.results.labTest',
                'imagingRequest',
                'drugTestRequest',
                'invoice.items',
            ])
            ->latest('visit_date')
            ->get();

        // Build visit history
        $visitHistory = $visits->map(fn($v) => [
            'id'               => $v->id,
            'case_number'      => $v->case_number,
            'visit_type'       => $v->visit_type,
            'visit_date'       => $v->visit_date->format('M d, Y'),
            'visit_date_full'  => $v->visit_date->format('M d, Y h:i A'),
            'visit_date_sort'  => $v->visit_date->timestamp,
            'status'           => $v->status,
            'employer_company' => $v->employer_company,
            'is_field_visit'   => $v->is_field_visit ?? false,
            'services'         => collect($v->invoice?->items ?? [])
                ->map(fn($i) => ['code' => $i->service_code, 'name' => $i->service_name, 'price' => $i->unit_price])
                ->toArray(),
            'total_amount'     => $v->invoice?->total_amount ?? 0,
            'invoice_status'   => $v->invoice?->status ?? 'none',
            // Vitals snapshot
            'vitals' => $v->vitals ? [
                'weight_kg'   => $v->vitals->weight_kg,
                'height_cm'   => $v->vitals->height_cm,
                'bmi'         => $v->vitals->bmi,
                'bmi_category'=> $v->vitals->bmi_category,
                'bp'          => $v->vitals->blood_pressure_systolic
                    ? "{$v->vitals->blood_pressure_systolic}/{$v->vitals->blood_pressure_diastolic}"
                    : null,
                'pulse_rate'  => $v->vitals->pulse_rate,
                'temperature' => $v->vitals->temperature_celsius,
            ] : null,
            // Lab summary
            'lab' => $v->labRequest ? [
                'status'        => $v->labRequest->status,
                'has_abnormal'  => $v->labRequest->results->where('is_abnormal', true)->count() > 0,
                'abnormal_count'=> $v->labRequest->results->where('is_abnormal', true)->count(),
                'request_number'=> $v->labRequest->request_number,
                'result_date'   => $v->labRequest->result_date?->format('M d, Y'),
                'results'       => $v->labRequest->results->map(fn($r) => [
                    'test_code'    => $r->labTest?->test_code,
                    'test_name'    => $r->labTest?->test_name,
                    'category'     => $r->labTest?->category,
                    'result_value' => $r->result_value,
                    'unit'         => $r->unit,
                    'normal_range' => $r->normal_range_display,
                    'is_abnormal'  => $r->is_abnormal,
                    'flag'         => $r->abnormal_flag,
                ])->sortBy(fn($r) => match($r['category']) {
                    'hematology' => 1, 'chemistry' => 2,
                    'urinalysis' => 3, 'stool' => 4, 'serology' => 5, default => 6
                })->values()->toArray(),
            ] : null,
            // Imaging summary
            'imaging' => $v->imagingRequest ? [
                'status'          => $v->imagingRequest->status,
                'imaging_type'    => $v->imagingRequest->imaging_type_label,
                'impression'      => $v->imagingRequest->impression,
                'is_provisional'  => $v->imagingRequest->is_provisional,
                'request_number'  => $v->imagingRequest->request_number,
                'exam_date'       => $v->imagingRequest->exam_date instanceof \Carbon\Carbon
                    ? $v->imagingRequest->exam_date->format('M d, Y')
                    : $v->imagingRequest->exam_date,
            ] : null,
            // Drug test summary
            'drug_test' => $v->drugTestRequest ? [
                'status'      => $v->drugTestRequest->status,
                'result'      => $v->drugTestRequest->result,
                'drugs_label' => $v->drugTestRequest->drugs_label,
                'code_number' => $v->drugTestRequest->code_number,
                'specimen_date'=> $v->drugTestRequest->specimen_date instanceof \Carbon\Carbon
                    ? $v->drugTestRequest->specimen_date->format('M d, Y')
                    : $v->drugTestRequest->specimen_date,
            ] : null,
            // Consultation summary
            'consultation' => $v->consultation ? [
                'is_finalized'      => $v->consultation->is_finalized,
                'pe_classification' => $v->consultation->pe_classification,
                'pe_classification_label' => $v->consultation->pe_classification_label,
                'essentially_normal'=> $v->consultation->essentially_normal,
                'pe_findings'       => $v->consultation->pe_findings,
                'soap_assessment'   => $v->consultation->soap_assessment,
                'icd10_code'        => $v->consultation->icd10_code,
                'icd10_description' => $v->consultation->icd10_description,
                'doctor_notes'      => $v->consultation->doctor_notes,
                'finalized_at'      => $v->consultation->finalized_at?->format('M d, Y h:i A'),
            ] : null,
        ]);

         $prescriptions = \App\Models\Prescription::where('patient_id', $patient->id)
        ->latest('rx_date')
        ->get()
        ->map(fn($rx) => [
            'id'             => $rx->id,
            'rx_number'      => $rx->rx_number,
            'rx_date'        => $rx->rx_date->format('M d, Y'),
            'items'          => $rx->items,
            'items_count'    => count($rx->items ?? []),
            'notes'          => $rx->notes,
            'is_controlled'  => $rx->is_controlled,
            'doctor_name'    => $rx->doctor_name,
            'visit_id'       => $rx->patient_visit_id,
        ]);

        // Stats summary
        $totalVisits    = $visits->count();
        $lastVisit      = $visits->first();
        $peCount        = $visits->whereIn('visit_type', ['pre_employment','annual_pe','exit_pe'])->count();
        $labAbnormals   = $visits->sum(fn($v) => $v->labRequest?->results->where('is_abnormal',true)->count() ?? 0);

        return inertia('Patients/Show', [
            'patient' => [
                'id'                       => $patient->id,
                'patient_code'             => $patient->patient_code,
                'full_name'                => $patient->full_name,
                'first_name'               => $patient->first_name,
                'last_name'                => $patient->last_name,
                'middle_name'              => $patient->middle_name,
                'date_of_birth'            => $patient->date_of_birth->format('M d, Y'),
                'date_of_birth_raw'        => $patient->date_of_birth->format('Y-m-d'),
                'age'                      => $patient->age,
                'age_sex'                  => $patient->age_sex,
                'sex'                      => $patient->sex,
                'civil_status'             => $patient->civil_status,
                'contact_number'           => $patient->contact_number,
                'email'                    => $patient->email,
                'address'                  => $patient->address,
                'philhealth_number'        => $patient->philhealth_number,
                'blood_type'               => $patient->blood_type,
                'occupation'               => $patient->occupation,
                'emergency_contact_name'   => $patient->emergency_contact_name,
                'emergency_contact_number' => $patient->emergency_contact_number,
                'photo_path'               => $patient->photo_path
                    ? asset('storage/' . $patient->photo_path)
                    : null,
                'visit_type_default'       => $patient->visit_type_default,
                'is_active'                => $patient->is_active,
                'registered_by'            => $patient->registeredBy?->name,
                'created_at'               => $patient->created_at->format('M d, Y'),
            ],
            'visitHistory' => $visitHistory,
            'stats' => [
                'total_visits'   => $totalVisits,
                'pe_count'       => $peCount,
                'last_visit_date'=> $lastVisit?->visit_date->format('M d, Y'),
                'last_visit_type'=> $lastVisit?->visit_type,
                'lab_abnormals'  => $labAbnormals,
                'companies'      => $visits->pluck('employer_company')
                    ->filter()->unique()->values()->toArray(),
                'rx_count' => $prescriptions->count(),
            ],
            'prescriptions' => $prescriptions,
        ]);
    }

    // ── EDIT FORM ─────────────────────────────────────────

    public function edit(Patient $patient)
{
    return inertia('Patients/Edit', [
        'patient' => [
            'id'                       => $patient->id,
            'patient_code'             => $patient->patient_code,
            'full_name'                => $patient->full_name,
            'first_name'               => $patient->first_name,
            'last_name'                => $patient->last_name,
            'middle_name'              => $patient->middle_name,
            'date_of_birth'            => $patient->date_of_birth->format('Y-m-d'), // ← clean format
            'sex'                      => $patient->sex,
            'civil_status'             => $patient->civil_status,
            'contact_number'           => $patient->contact_number,
            'email'                    => $patient->email,
            'address'                  => $patient->address,
            'philhealth_number'        => $patient->philhealth_number,
            'blood_type'               => $patient->blood_type,
            'occupation'               => $patient->occupation,
            'emergency_contact_name'   => $patient->emergency_contact_name,
            'emergency_contact_number' => $patient->emergency_contact_number,
            'photo_path'               => $patient->photo_path,
            'visit_type_default'       => $patient->visit_type_default,
            'is_active'                => $patient->is_active,
            'created_at'               => $patient->created_at->format('M d, Y'),
        ],
    ]);
}

    // ── UPDATE ────────────────────────────────────────────

    public function update(Request $request, Patient $patient)
    {
        $this->authorize('patients.edit');

        $validated = $request->validate([
            'first_name'               => ['required', 'string', 'max:100'],
            'last_name'                => ['required', 'string', 'max:100'],
            'middle_name'              => ['nullable', 'string', 'max:100'],
            'date_of_birth'            => ['required', 'date', 'before:today'],
            'sex'                      => ['required', 'in:male,female'],
            'civil_status'             => ['required', 'in:single,married,widowed,separated'],
            'contact_number'           => ['nullable', 'string', 'max:15'],
            'email'                    => ['nullable', 'email', 'max:150'],
            'address'                  => ['nullable', 'string'],
            'philhealth_number'        => ['nullable', 'string', 'max:20'],
            'blood_type'               => ['nullable', 'in:A+,A-,B+,B-,O+,O-,AB+,AB-'],
            'occupation'               => ['nullable', 'string', 'max:100'],
            'emergency_contact_name'   => ['nullable', 'string', 'max:150'],
            'emergency_contact_number' => ['nullable', 'string', 'max:15'],
            'visit_type_default'       => ['required', 'in:opd,pre_employment,annual_pe,exit_pe,follow_up,lab_only'],
            'is_active'                => ['boolean'],
            'photo'                    => ['nullable', 'image', 'max:2048'],
        ]);

        // Handle new photo upload
        if ($request->hasFile('photo')) {
            // Delete old photo
            if ($patient->photo_path) {
                Storage::disk('public')->delete($patient->photo_path);
            }
            $validated['photo_path'] = $request->file('photo')
                ->store('patients/photos', 'public');
        }

        unset($validated['photo']);
        $patient->update($validated);

        return redirect()
            ->route('patients.show', $patient)
            ->with('success', "Patient record updated successfully.");
    }

    // ── ARCHIVE (Soft Delete) ─────────────────────────────

    public function destroy(Patient $patient)
    {
        $this->authorize('patients.delete');

        $patient->update(['is_active' => false]);
        $patient->delete();

        return redirect()
            ->route('patients.index')
            ->with('success', "Patient {$patient->full_name} has been archived.");
    }
}
