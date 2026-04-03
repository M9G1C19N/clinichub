<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN department ENUM(
            'laboratory',
            'xray_utz',
            'drug_test',
            'reception',
            'nursing',
            'admin',
            'doctor',
            'billing'
        ) NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN department ENUM(
            'laboratory',
            'xray_utz',
            'drug_test',
            'reception',
            'nursing',
            'admin',
            'doctor'
        ) NULL");
    }
};
