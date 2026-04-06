<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE lab_tests MODIFY COLUMN category ENUM(
            'hematology','chemistry','urinalysis','serology','stool','thyroid','hba1c','ogtt','other'
        ) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE lab_tests MODIFY COLUMN category ENUM(
            'hematology','chemistry','urinalysis','serology','stool','thyroid','other'
        ) NOT NULL");
    }
};
