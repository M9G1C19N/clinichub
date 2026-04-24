<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\ServiceCatalog;

return new class extends Migration
{
    public function up(): void
    {
        ServiceCatalog::firstOrCreate(
            ['service_code' => 'AUDIOMETRY'],
            [
                'service_name'     => 'Screening Audiometry',
                'category'         => 'laboratory',
                'room'             => 'laboratory',
                'base_price'       => 500.00,
                'turnaround_hours' => 1,
                'requires_fasting' => false,
                'is_active'        => true,
            ]
        );
    }

    public function down(): void
    {
        ServiceCatalog::where('service_code', 'AUDIOMETRY')->delete();
    }
};
