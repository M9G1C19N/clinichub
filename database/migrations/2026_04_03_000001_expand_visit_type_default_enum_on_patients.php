<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE patients MODIFY COLUMN visit_type_default
            ENUM('opd','pre_employment','annual_pe','exit_pe','follow_up','lab_only')
            NOT NULL DEFAULT 'opd'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE patients MODIFY COLUMN visit_type_default
            ENUM('opd','pre_employment')
            NOT NULL DEFAULT 'opd'");
    }
};
