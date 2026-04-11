<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RefreshTransactionalData extends Command
{
    protected $signature = 'db:refresh-transactional
                            {--force : Skip confirmation prompt (use with caution)}';

    protected $description = 'Truncate all transactional data while preserving users, patients, esignatures, packages, service catalog, booking photos, and lab test catalog.';

    private array $tablesToTruncate = [
        // Queue
        'queue_tickets',
        'queue_room_assignments',
        'queue_counters',

        // Visits & clinical
        'patient_vitals',
        'consultations',
        'prescriptions',
        'appointments',

        // Lab, imaging, drug test
        'laboratory_results',
        'laboratory_requests',
        'imaging_requests',
        'drug_test_requests',

        // Billing
        'invoice_items',
        'payments',
        'invoices',

        // Visits (after dependents cleared)
        'patient_visits',

        // Logs & misc
        'activity_log',
        'field_sync_logs',
        'kiosk_checkins',
    ];

    private array $tablesToKeep = [
        'users',
        'roles',
        'permissions',
        'model_has_roles',
        'model_has_permissions',
        'role_has_permissions',
        'patients',
        'esignatures',
        'package_discounts',
        'service_catalog',
        'service_price_history',
        'booking_photos',
        'lab_tests',
        'migrations',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
    ];

    public function handle(): int
    {
        $this->info('');
        $this->info('  ClinicHub — Transactional Data Refresh');
        $this->info('  ──────────────────────────────────────');
        $this->info('');
        $this->info('  Tables that will be PRESERVED:');
        foreach ($this->tablesToKeep as $t) {
            $this->line("    ✔  {$t}");
        }

        $this->info('');
        $this->warn('  Tables that will be TRUNCATED (ALL ROWS DELETED):');
        foreach ($this->tablesToTruncate as $t) {
            $this->line("    ✖  {$t}");
        }

        $this->info('');
        $this->warn('  ⚠  This action is IRREVERSIBLE. Create a backup first if needed.');
        $this->info('');

        if (!$this->option('force')) {
            if (!$this->confirm('  Are you sure you want to proceed?', false)) {
                $this->info('  Aborted.');
                return self::SUCCESS;
            }

            $confirm = $this->ask('  Type REFRESH to confirm');
            if (strtoupper(trim($confirm)) !== 'REFRESH') {
                $this->error('  Confirmation failed. Aborted.');
                return self::FAILURE;
            }
        }

        $this->info('');
        $this->info('  Starting truncation...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $failed = [];
        foreach ($this->tablesToTruncate as $table) {
            try {
                DB::table($table)->truncate();
                $this->line("    <fg=green>✔</> Truncated: {$table}");
            } catch (\Throwable $e) {
                $failed[] = $table;
                $this->line("    <fg=red>✖</> Failed:    {$table} — {$e->getMessage()}");
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->info('');

        if (empty($failed)) {
            $this->info('  ✔  All transactional data cleared successfully.');
        } else {
            $this->warn('  ⚠  Completed with errors on: ' . implode(', ', $failed));
        }

        $this->info('');
        return empty($failed) ? self::SUCCESS : self::FAILURE;
    }
}
