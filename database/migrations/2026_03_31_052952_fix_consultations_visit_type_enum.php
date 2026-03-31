<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;  // ← REQUIRED

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE consultations MODIFY COLUMN visit_type
            ENUM('opd','pre_employment','annual_pe','exit_pe','follow_up','lab_only')
            NOT NULL DEFAULT 'opd'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE consultations MODIFY COLUMN visit_type
            ENUM('opd','pre_employment','follow_up','lab_only')
            NOT NULL DEFAULT 'opd'");
    }
};
