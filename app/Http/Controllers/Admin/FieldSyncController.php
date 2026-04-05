<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\DrugTestRequest;
use App\Models\FieldSyncLog;
use App\Models\ImagingRequest;
use App\Models\LaboratoryRequest;
use App\Models\LaboratoryResult;
use App\Models\Patient;
use App\Models\PatientVisit;
use App\Models\PatientVital;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use Inertia\Response;

class FieldSyncController extends Controller
{
    private const SESSION_KEY = 'field_sync_preview';
    private const TEMP_DIR    = 'field_sync_temp';

    // ─────────────────────────────────────────────────────────────────
    // GET /admin/field-sync
    // ─────────────────────────────────────────────────────────────────

    public function index(): Response
    {
        $logs = FieldSyncLog::with('importedBy')
            ->latest()
            ->paginate(15);

        $logs->getCollection()->transform(fn($log) => [
            'id'               => $log->id,
            'original_filename'=> $log->original_filename,
            'device_id'        => $log->device_id,
            'visits_imported'  => $log->visits_imported,
            'patients_created' => $log->patients_created,
            'patients_matched' => $log->patients_matched,
            'visits_skipped'   => $log->visits_skipped,
            'skipped_details'  => $log->skipped_details,
            'imported_by'      => $log->importedBy?->name,
            'created_at'       => $log->created_at->format('Y-m-d H:i:s'),
        ]);

        return Inertia::render('Admin/FieldSync', [
            'syncLogs'        => $logs,
            'preview'         => session(self::SESSION_KEY),
            'pendingExportCount' => PatientVisit::where('is_field_visit', true)
                ->whereNull('case_number')
                ->count(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // GET /admin/field-sync/export  — used on the FIELD SERVER
    // Exports all unsynced field visits as a single JSON package
    // ─────────────────────────────────────────────────────────────────

    public function export()
    {
        $visits = PatientVisit::with([
            'patient',
            'vitals',
            'consultation',
            'labRequest.results.labTest',
            'imagingRequests',
            'drugTestRequest',
        ])
        ->where('is_field_visit', true)
        ->whereNull('case_number')
        ->where(fn($q) => $q->where('synced_from_field', false)->orWhereNull('synced_from_field'))
        ->get();

        if ($visits->isEmpty()) {
            return response()->json([
                'error' => 'No unsynced field visits found to export.',
            ], 422);
        }

        $payload = [
            'clinichub_field_export' => true,
            'version'                => '2.0',
            'exported_at'            => now()->toISOString(),
            'exported_by'            => Auth::user()->name,
            'device_id'              => config('app.name') . '@' . gethostname(),
            'visits'                 => $visits->map(fn($v) => $this->serializeVisit($v))->values(),
        ];

        $filename = 'field_export_' . now()->format('Y_m_d_His') . '.json';

        return response()->streamDownload(
            fn() => print json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            $filename,
            ['Content-Type' => 'application/json']
        );
    }

    // ─────────────────────────────────────────────────────────────────
    // POST /admin/field-sync/preview  — main server: upload + analyze
    // ─────────────────────────────────────────────────────────────────

    public function preview(Request $request): RedirectResponse
    {
        $request->validate([
            'sync_file' => 'required|file|mimes:json|max:20480',
        ]);

        $raw  = file_get_contents($request->file('sync_file')->getRealPath());
        $data = json_decode($raw, true);

        if (!$data || empty($data['clinichub_field_export']) || empty($data['visits'])) {
            return back()->with('error', 'Invalid file. Please use a .json file exported from ClinicHub field server.');
        }

        if (empty($data['visits'])) {
            return back()->with('error', 'The export file contains no visits.');
        }

        // Store temp copy
        $tempDir = storage_path('app/' . self::TEMP_DIR);
        if (!File::isDirectory($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }
        $tempKey  = uniqid('sync_', true);
        File::put($tempDir . '/' . $tempKey . '.json', $raw);

        session([self::SESSION_KEY => $this->buildPreview(
            $data,
            $tempKey,
            $request->file('sync_file')->getClientOriginalName()
        )]);

        return redirect()->route('admin.field-sync.index')
            ->with('success', count($data['visits']) . ' visit(s) analyzed. Review below before confirming.');
    }

    // ─────────────────────────────────────────────────────────────────
    // POST /admin/field-sync/import  — main server: confirm import
    // ─────────────────────────────────────────────────────────────────

    public function import(): RedirectResponse
    {
        $preview = session(self::SESSION_KEY);
        if (!$preview) {
            return back()->with('error', 'No preview found. Please upload the file again.');
        }

        $tempPath = storage_path('app/' . self::TEMP_DIR . '/' . $preview['temp_key'] . '.json');
        if (!File::exists($tempPath)) {
            session()->forget(self::SESSION_KEY);
            return back()->with('error', 'Temp file expired. Please upload the file again.');
        }

        $data            = json_decode(File::get($tempPath), true);
        $visitsImported  = 0;
        $patientsCreated = 0;
        $patientsMatched = 0;
        $visitsSkipped   = 0;
        $skippedDetails  = [];
        $syncLog         = null;

        DB::transaction(function () use (
            $data, $preview,
            &$visitsImported, &$patientsCreated, &$patientsMatched,
            &$visitsSkipped, &$skippedDetails, &$syncLog
        ) {
            $syncLog = FieldSyncLog::create([
                'original_filename' => $preview['filename'],
                'device_id'         => $data['device_id'] ?? null,
                'visits_imported'   => 0,
                'patients_created'  => 0,
                'patients_matched'  => 0,
                'visits_skipped'    => 0,
                'imported_by'       => Auth::id(),
            ]);

            foreach ($data['visits'] as $entry) {
                try {
                    [$patient, $isNew] = $this->resolvePatient($entry['patient']);

                    if ($isNew) $patientsCreated++;
                    else        $patientsMatched++;

                    $visit = $this->createVisit($entry['visit'], $patient->id, $data['device_id'] ?? null, $syncLog->id);

                    if (!empty($entry['vitals']))         $this->createVitals($entry['vitals'], $patient->id, $visit->id);
                    if (!empty($entry['consultation']))   $this->createConsultation($entry['consultation'], $patient->id, $visit->id, $entry['visit']['visit_type']);
                    if (!empty($entry['lab_request']))    $this->createLabRequest($entry['lab_request'], $patient->id, $visit->id);
                    if (!empty($entry['imaging_requests'])) {
                        foreach ($entry['imaging_requests'] as $img) {
                            $this->createImagingRequest($img, $patient->id, $visit->id);
                        }
                    }
                    if (!empty($entry['drug_test_request'])) $this->createDrugTestRequest($entry['drug_test_request'], $patient->id, $visit->id);

                    $visitsImported++;
                } catch (\Throwable $e) {
                    $visitsSkipped++;
                    $skippedDetails[] = [
                        'field_temp_id' => $entry['field_temp_id'] ?? null,
                        'patient'       => trim(($entry['patient']['first_name'] ?? '') . ' ' . ($entry['patient']['last_name'] ?? '')),
                        'reason'        => $e->getMessage(),
                    ];
                }
            }

            $syncLog->update([
                'visits_imported'  => $visitsImported,
                'patients_created' => $patientsCreated,
                'patients_matched' => $patientsMatched,
                'visits_skipped'   => $visitsSkipped,
                'skipped_details'  => $skippedDetails ?: null,
            ]);
        });

        File::delete($tempPath);
        session()->forget(self::SESSION_KEY);
        activity()->log("Field sync: {$visitsImported} visits imported, {$patientsCreated} new patients, {$visitsSkipped} skipped.");

        $msg = "{$visitsImported} visit(s) imported and assigned case numbers.";
        if ($patientsCreated) $msg .= " {$patientsCreated} new patient(s) registered.";
        if ($visitsSkipped)   $msg .= " {$visitsSkipped} skipped — see log.";

        return redirect()->route('admin.field-sync.index')->with('success', $msg);
    }

    // ─────────────────────────────────────────────────────────────────
    // POST /admin/field-sync/cancel-preview
    // ─────────────────────────────────────────────────────────────────

    public function cancelPreview(): RedirectResponse
    {
        $preview = session(self::SESSION_KEY);
        if ($preview) {
            File::delete(storage_path('app/' . self::TEMP_DIR . '/' . $preview['temp_key'] . '.json'));
            session()->forget(self::SESSION_KEY);
        }
        return redirect()->route('admin.field-sync.index');
    }

    // ─────────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────────

    private function serializeVisit(PatientVisit $v): array
    {
        $p = $v->patient;

        return [
            'field_temp_id' => $v->id,
            'patient' => [
                'patient_code'             => $p->patient_code,
                'first_name'               => $p->first_name,
                'last_name'                => $p->last_name,
                'middle_name'              => $p->middle_name,
                'date_of_birth'            => $p->date_of_birth?->format('Y-m-d'),
                'sex'                      => $p->sex,
                'civil_status'             => $p->civil_status,
                'contact_number'           => $p->contact_number,
                'email'                    => $p->email,
                'address'                  => $p->address,
                'philhealth_number'        => $p->philhealth_number,
                'blood_type'               => $p->blood_type,
                'occupation'               => $p->occupation,
                'emergency_contact_name'   => $p->emergency_contact_name,
                'emergency_contact_number' => $p->emergency_contact_number,
                'visit_type_default'       => $p->visit_type_default,
            ],
            'visit' => [
                'visit_type'        => $v->visit_type,
                'visit_date'        => $v->visit_date->format('Y-m-d H:i:s'),
                'chief_complaint'   => $v->chief_complaint,
                'employer_company'  => $v->employer_company,
                'services_selected' => $v->services_selected,
                'status'            => $v->status,
                'result_claim_date' => $v->result_claim_date?->format('Y-m-d'),
            ],
            'vitals'              => $v->vitals        ? $this->serializeVitals($v->vitals)              : null,
            'consultation'        => $v->consultation  ? $this->serializeConsultation($v->consultation)  : null,
            'lab_request'         => $v->labRequest    ? $this->serializeLabRequest($v->labRequest)      : null,
            'imaging_requests'    => $v->imagingRequests->map(fn($i) => $this->serializeImagingRequest($i))->values()->all(),
            'drug_test_request'   => $v->drugTestRequest ? $this->serializeDrugTest($v->drugTestRequest) : null,
        ];
    }

    private function serializeVitals(PatientVital $vt): array
    {
        return collect($vt->getFillable())
            ->reject(fn($f) => in_array($f, ['patient_id', 'patient_visit_id', 'recorded_by']))
            ->mapWithKeys(fn($f) => [$f => $vt->$f])
            ->all();
    }

    private function serializeConsultation(Consultation $c): array
    {
        return collect($c->getFillable())
            ->reject(fn($f) => in_array($f, ['patient_id', 'patient_visit_id', 'doctor_id', 'ecg_noted_by_user_id']))
            ->mapWithKeys(fn($f) => [$f => $c->$f])
            ->all();
    }

    private function serializeLabRequest(LaboratoryRequest $lr): array
    {
        $fields = collect($lr->getFillable())
            ->reject(fn($f) => in_array($f, ['patient_id', 'patient_visit_id', 'requested_by', 'released_by', 'request_number']))
            ->mapWithKeys(fn($f) => [$f => $lr->$f])
            ->all();

        $fields['results'] = $lr->results->map(fn($r) => [
            'lab_test_id'          => $r->lab_test_id,
            'result_value'         => $r->result_value,
            'unit'                 => $r->unit,
            'normal_range_display' => $r->normal_range_display,
            'is_abnormal'          => $r->is_abnormal,
            'abnormal_flag'        => $r->abnormal_flag,
            'remarks'              => $r->remarks,
        ])->all();

        return $fields;
    }

    private function serializeImagingRequest(ImagingRequest $img): array
    {
        return collect($img->getFillable())
            ->reject(fn($f) => in_array($f, ['patient_id', 'patient_visit_id', 'requested_by', 'released_by', 'request_number']))
            ->mapWithKeys(fn($f) => [$f => $img->$f])
            ->all();
    }

    private function serializeDrugTest(DrugTestRequest $dt): array
    {
        return collect($dt->getFillable())
            ->reject(fn($f) => in_array($f, ['patient_id', 'patient_visit_id', 'collected_by', 'code_number', 'accession_number']))
            ->mapWithKeys(fn($f) => [$f => $dt->$f])
            ->all();
    }

    // ─────────────────────────────────────────────────────────────────

    private function buildPreview(array $data, string $tempKey, string $originalFilename): array
    {
        $items = [];
        foreach ($data['visits'] as $entry) {
            $pd = $entry['patient'];
            $existing = Patient::where('patient_code', $pd['patient_code'])->first()
                ?? Patient::where('first_name', $pd['first_name'])
                          ->where('last_name', $pd['last_name'])
                          ->whereDate('date_of_birth', $pd['date_of_birth'] ?? null)
                          ->first();

            $modules = [];
            if (!empty($entry['vitals']))           $modules[] = 'Vitals';
            if (!empty($entry['consultation']))     $modules[] = 'Consultation';
            if (!empty($entry['lab_request']))      $modules[] = 'Lab';
            if (!empty($entry['imaging_requests'])) $modules[] = 'Imaging (' . count($entry['imaging_requests']) . ')';
            if (!empty($entry['drug_test_request']))$modules[] = 'Drug Test';

            $items[] = [
                'field_temp_id'  => $entry['field_temp_id'] ?? null,
                'patient_name'   => trim(($pd['first_name'] ?? '') . ' ' . ($pd['last_name'] ?? '')),
                'patient_code'   => $pd['patient_code'] ?? null,
                'date_of_birth'  => $pd['date_of_birth'] ?? null,
                'visit_type'     => $entry['visit']['visit_type'] ?? null,
                'visit_date'     => $entry['visit']['visit_date'] ?? null,
                'status'         => $entry['visit']['status'] ?? null,
                'modules'        => $modules,
                'patient_action' => $existing ? 'match' : 'create',
                'matched_code'   => $existing?->patient_code,
            ];
        }

        return [
            'temp_key'    => $tempKey,
            'filename'    => $originalFilename,
            'device_id'   => $data['device_id'] ?? null,
            'exported_at' => $data['exported_at'] ?? null,
            'exported_by' => $data['exported_by'] ?? null,
            'version'     => $data['version'] ?? '1.0',
            'items'       => $items,
        ];
    }

    // ─────────────────────────────────────────────────────────────────

    private function resolvePatient(array $pd): array
    {
        $patient = Patient::where('patient_code', $pd['patient_code'])->first()
            ?? Patient::where('first_name', $pd['first_name'])
                      ->where('last_name', $pd['last_name'])
                      ->whereDate('date_of_birth', $pd['date_of_birth'] ?? null)
                      ->first();

        if ($patient) return [$patient, false];

        $patient = Patient::create([
            'first_name'               => $pd['first_name'],
            'last_name'                => $pd['last_name'],
            'middle_name'              => $pd['middle_name'] ?? null,
            'date_of_birth'            => $pd['date_of_birth'] ?? null,
            'sex'                      => $pd['sex'] ?? null,
            'civil_status'             => $pd['civil_status'] ?? null,
            'contact_number'           => $pd['contact_number'] ?? null,
            'email'                    => $pd['email'] ?? null,
            'address'                  => $pd['address'] ?? null,
            'philhealth_number'        => $pd['philhealth_number'] ?? null,
            'blood_type'               => $pd['blood_type'] ?? null,
            'occupation'               => $pd['occupation'] ?? null,
            'emergency_contact_name'   => $pd['emergency_contact_name'] ?? null,
            'emergency_contact_number' => $pd['emergency_contact_number'] ?? null,
            'visit_type_default'       => $pd['visit_type_default'] ?? 'opd',
            'created_by'               => Auth::id(),
        ]);
        return [$patient, true];
    }

    private function createVisit(array $vd, int $patientId, ?string $deviceId, int $syncLogId): PatientVisit
    {
        return PatientVisit::create([
            'patient_id'        => $patientId,
            'visit_type'        => $vd['visit_type'],
            'visit_date'        => $vd['visit_date'],
            'chief_complaint'   => $vd['chief_complaint'] ?? null,
            'employer_company'  => $vd['employer_company'] ?? null,
            'services_selected' => $vd['services_selected'] ?? null,
            'status'            => $vd['status'] ?? 'completed',
            'result_claim_date' => $vd['result_claim_date'] ?? null,
            'created_by'        => Auth::id(),
            'is_field_visit'    => true,
            'synced_from_field' => true,
            'synced_at'         => now(),
            'field_device_id'   => $deviceId,
            'field_sync_log_id' => $syncLogId,
        ]);
    }

    private function createVitals(array $vt, int $patientId, int $visitId): void
    {
        PatientVital::create(array_merge($vt, [
            'patient_id'       => $patientId,
            'patient_visit_id' => $visitId,
            'recorded_by'      => Auth::id(),
        ]));
    }

    private function createConsultation(array $cd, int $patientId, int $visitId, string $visitType): void
    {
        Consultation::create(array_merge($cd, [
            'patient_id'       => $patientId,
            'patient_visit_id' => $visitId,
            'visit_type'       => $visitType,
            'doctor_id'        => Auth::id(),
        ]));
    }

    private function createLabRequest(array $ld, int $patientId, int $visitId): void
    {
        $results = $ld['results'] ?? [];
        unset($ld['results']);

        $lr = LaboratoryRequest::create(array_merge($ld, [
            'patient_id'       => $patientId,
            'patient_visit_id' => $visitId,
            'requested_by'     => Auth::id(),
        ]));

        foreach ($results as $r) {
            LaboratoryResult::create(array_merge($r, [
                'lab_request_id' => $lr->id,
                'performed_by'   => Auth::id(),
            ]));
        }
    }

    private function createImagingRequest(array $img, int $patientId, int $visitId): void
    {
        ImagingRequest::create(array_merge($img, [
            'patient_id'       => $patientId,
            'patient_visit_id' => $visitId,
            'requested_by'     => Auth::id(),
        ]));
    }

    private function createDrugTestRequest(array $dt, int $patientId, int $visitId): void
    {
        DrugTestRequest::create(array_merge($dt, [
            'patient_id'       => $patientId,
            'patient_visit_id' => $visitId,
            'collected_by'     => Auth::id(),
        ]));
    }
}
