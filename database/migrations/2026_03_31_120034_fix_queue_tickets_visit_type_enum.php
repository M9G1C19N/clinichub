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
         DB::statement("ALTER TABLE queue_tickets MODIFY COLUMN visit_type
            ENUM('opd','pre_employment','annual_pe','exit_pe','follow_up','lab_only')
            NOT NULL DEFAULT 'opd'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
          DB::statement("ALTER TABLE queue_tickets MODIFY COLUMN visit_type
            ENUM('opd','pre_employment','follow_up','lab_only')
            NOT NULL DEFAULT 'opd'");
    }
};
