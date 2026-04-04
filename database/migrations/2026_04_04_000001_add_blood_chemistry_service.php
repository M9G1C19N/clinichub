<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('service_catalog')->insertOrIgnore([
            'service_code'     => 'BLOOD_CHEMISTRY',
            'service_name'     => 'Blood Chemistry Panel',
            'category'         => 'laboratory',
            'room'             => 'laboratory',
            'base_price'       => 800.00,
            'turnaround_hours' => 4,
            'requires_fasting' => true,
            'is_active'        => true,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('service_catalog')->where('service_code', 'BLOOD_CHEMISTRY')->delete();
    }
};
