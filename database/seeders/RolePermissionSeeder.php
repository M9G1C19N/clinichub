<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // --- Define all permissions per module ---
        $permissions = [
            // Patient Management
            'patients.view', 'patients.create', 'patients.edit', 'patients.delete',
            'patient_visits.view', 'patient_visits.create', 'patient_visits.edit',

            // Queue & Routing
            'queue.view', 'queue.manage', 'queue.call', 'queue.skip', 'queue.transfer',

            // Reception & Billing
            'reception.view', 'invoices.view', 'invoices.create', 'invoices.edit',
            'payments.view', 'payments.create',

            // Nurse Intake
            'vitals.view', 'vitals.create', 'vitals.edit',

            // Laboratory
            'lab_requests.view', 'lab_requests.create', 'lab_requests.edit',
            'lab_results.view', 'lab_results.enter', 'lab_results.verify', 'lab_results.release',

            // X-Ray & Ultrasound
            'xray_requests.view', 'xray_requests.create',
            'xray_results.view', 'xray_results.enter', 'xray_results.verify',

            // Drug Test
            'drug_tests.view', 'drug_tests.create', 'drug_tests.enter', 'drug_tests.release',

            // Doctor / Consultation
            'consultations.view', 'consultations.create', 'consultations.edit',
            'prescriptions.view', 'prescriptions.create',
            'soap_notes.view', 'soap_notes.create', 'soap_notes.edit',

            // Admin
            'users.view', 'users.create', 'users.edit', 'users.delete',
            'service_catalog.view', 'service_catalog.manage',
            'reports.view', 'reports.export',
            'audit_logs.view',
            'esignatures.manage',
            'appointments.view', 'appointments.create', 'appointments.edit',

            'doctor_features',  // Grants nurse full doctor access
            'nurse_features',   // Grants doctor nurse workflow access
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // --- Define roles and assign permissions ---

        // ADMIN — full access
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions(Permission::all());

        // RECEPTIONIST
        $receptionist = Role::firstOrCreate(['name' => 'receptionist']);
        $receptionist->syncPermissions([
            'patients.view', 'patients.create', 'patients.edit',
            'patient_visits.view', 'patient_visits.create', 'patient_visits.edit',
            'queue.view', 'queue.manage', 'queue.call', 'queue.skip', 'queue.transfer',
            'reception.view',
            'invoices.view', 'invoices.create', 'invoices.edit',
            'payments.view', 'payments.create',
            'appointments.view', 'appointments.create', 'appointments.edit',
        ]);

        // NURSE
        $nurse = Role::firstOrCreate(['name' => 'nurse']);
        $nurse->syncPermissions([
            'patients.view', 'patient_visits.view',
            'queue.view', 'queue.call',
            'vitals.view', 'vitals.create', 'vitals.edit',
        ]);

        // DOCTOR
        $doctor = Role::firstOrCreate(['name' => 'doctor']);
        $doctor->syncPermissions([
            'patients.view',
            'patient_visits.view',
            'queue.view',
            'consultations.view', 'consultations.create', 'consultations.edit',
            'soap_notes.view', 'soap_notes.create', 'soap_notes.edit',
            'prescriptions.view', 'prescriptions.create',
            'lab_requests.view', 'lab_requests.create',
            'lab_results.view',
            'xray_requests.view', 'xray_requests.create',
            'xray_results.view',
            'drug_tests.view',
            'vitals.view',
            'appointments.view',
        ]);

        // LAB TECHNICIAN
        $labTech = Role::firstOrCreate(['name' => 'lab_technician']);
        $labTech->syncPermissions([
            'patients.view', 'patient_visits.view',
            'queue.view', 'queue.call',
            'lab_requests.view',
            'lab_results.view', 'lab_results.enter', 'lab_results.verify', 'lab_results.release',
        ]);

        // X-RAY / UTZ TECH
        $xrayTech = Role::firstOrCreate(['name' => 'xray_tech']);
        $xrayTech->syncPermissions([
            'patients.view', 'patient_visits.view',
            'queue.view', 'queue.call',
            'xray_requests.view',
            'xray_results.view', 'xray_results.enter', 'xray_results.verify',
        ]);

        // DRUG TEST STAFF
        $drugStaff = Role::firstOrCreate(['name' => 'drug_test_staff']);
        $drugStaff->syncPermissions([
            'patients.view', 'patient_visits.view',
            'queue.view', 'queue.call',
            'drug_tests.view', 'drug_tests.create', 'drug_tests.enter', 'drug_tests.release',
        ]);

        $this->command->info('✅ Roles and permissions seeded.');
    }
}
