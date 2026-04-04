<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE queue_room_assignments
            MODIFY COLUMN room ENUM(
                'laboratory',
                'xray_utz',
                'drug_test',
                'nurse_station',
                'interview_room'
            ) NOT NULL
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE queue_room_assignments
            MODIFY COLUMN room ENUM(
                'laboratory',
                'xray_utz',
                'drug_test',
                'interview_room'
            ) NOT NULL
        ");
    }
};
