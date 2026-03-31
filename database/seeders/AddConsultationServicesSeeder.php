<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ServiceCatalog;

class AddConsultationServicesSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            [
                'service_code'     => 'ANNUAL_PE',
                'service_name'     => 'Annual Physical Exam Consultation',
                'category'         => 'consultation',
                'base_price'       => 200.00,
                'turnaround_hours' => 0,
                'requires_fasting' => false,
                'is_active'        => true,
            ],
            [
                'service_code'     => 'EXIT_PE',
                'service_name'     => 'Exit Physical Exam Consultation',
                'category'         => 'consultation',
                'base_price'       => 200.00,
                'turnaround_hours' => 0,
                'requires_fasting' => false,
                'is_active'        => true,
            ],
            [
                'service_code'     => 'FOLLOW_UP',
                'service_name'     => 'Follow-up Consultation',
                'category'         => 'consultation',
                'base_price'       => 150.00,
                'turnaround_hours' => 0,
                'requires_fasting' => false,
                'is_active'        => true,
            ],
        ];

        foreach ($services as $svc) {
            ServiceCatalog::firstOrCreate(
                ['service_code' => $svc['service_code']],
                $svc
            );
        }

        $this->command->info('Done! Added: ANNUAL_PE, EXIT_PE, FOLLOW_UP');
    }
}
