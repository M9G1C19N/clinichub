<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // MySQL requires modifying enum by redefining it
        DB::statement("ALTER TABLE patient_visits MODIFY COLUMN visit_type
            ENUM('opd','pre_employment','annual_pe','exit_pe','follow_up','lab_only')
            NOT NULL DEFAULT 'opd'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE patient_visits MODIFY COLUMN visit_type
            ENUM('opd','pre_employment','follow_up','lab_only')
            NOT NULL DEFAULT 'opd'");
    }
};
