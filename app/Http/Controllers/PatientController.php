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
            'visit_type_default'       => ['required', 'in:opd,pre_employment'],
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

        $patient->load([
            'registeredBy',
            'visits' => fn($q) => $q->with('vitals')->latest()->limit(10),
        ]);

        return inertia('Patients/Show', [
            'patient' => [
                'id'                       => $patient->id,
                'patient_code'             => $patient->patient_code,
                'full_name'                => $patient->full_name,
                'first_name'               => $patient->first_name,
                'last_name'                => $patient->last_name,
                'middle_name'              => $patient->middle_name,
                'date_of_birth'            => $patient->date_of_birth->format('Y-m-d'),
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
                'photo_path'               => $patient->photo_path,
                'visit_type_default'       => $patient->visit_type_default,
                'is_active'                => $patient->is_active,
                'registered_by'            => $patient->registeredBy?->name,
                'created_at'               => $patient->created_at->format('M d, Y'),
                        'visits' => $patient->visits->map(fn($v) => [
                'id'         => $v->id,
                'visit_type' => $v->visit_type,
                'status'     => $v->status,
                'visit_date' => $v->visit_date->format('M d, Y h:i A'),
                'has_vitals' => $v->vitals !== null,
            ]),
            ],
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
            'visit_type_default'       => ['required', 'in:opd,pre_employment'],
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
