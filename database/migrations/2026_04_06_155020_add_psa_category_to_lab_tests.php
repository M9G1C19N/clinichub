<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add 'psa' to the category enum
        DB::statement("ALTER TABLE lab_tests MODIFY COLUMN category ENUM(
            'hematology','chemistry','urinalysis','serology','stool','thyroid','hba1c','ogtt','psa','other'
        ) NOT NULL");

        // Move PSA test to its own category
        DB::statement("UPDATE lab_tests SET category = 'psa' WHERE test_code = 'PSA'");
    }

    public function down(): void
    {
        DB::statement("UPDATE lab_tests SET category = 'serology' WHERE test_code = 'PSA'");

        DB::statement("ALTER TABLE lab_tests MODIFY COLUMN category ENUM(
            'hematology','chemistry','urinalysis','serology','stool','thyroid','hba1c','ogtt','other'
        ) NOT NULL");
    }
};
