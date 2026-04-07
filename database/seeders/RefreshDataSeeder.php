<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RefreshDataSeeder extends Seeder
{
    /**
     * Truncate all transactional tables while preserving:
     * - patients
     * - service_catalog, service_price_history
     * - lab_tests
     * - booking_photos
     * - esignatures
     * - users, roles, permissions, model_has_roles, model_has_permissions, role_has_permissions
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $tables = [
            'queue_tickets',
            'queue_room_assignments',
            'queue_counters',
            'patient_vitals',
            'laboratory_results',
            'laboratory_requests',
            'imaging_requests',
            'drug_test_requests',
            'prescriptions',
            'consultations',
            'invoice_items',
            'payments',
            'invoices',
            'appointments',
            'kiosk_checkins',
            'field_sync_logs',
            'activity_log',
            'patient_visits',
            'jobs',
            'failed_jobs',
            'cache',
            'cache_locks',
        ];

        foreach ($tables as $table) {
            if (DB::getSchemaBuilder()->hasTable($table)) {
                DB::table($table)->truncate();
                $this->command->info("Truncated: {$table}");
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('');
        $this->command->info('Re-seeding queue counters...');
        $this->call(QueueCounterSeeder::class);

        $this->command->info('');
        $this->command->info('Database refreshed. Preserved: patients, service_catalog, lab_tests, booking_photos, esignatures, users/roles.');
    }
}
