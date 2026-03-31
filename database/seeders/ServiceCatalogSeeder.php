<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\ServiceCatalog;

class ServiceCatalogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $services = [
            // ── Laboratory ──────────────────────────────
            ['code' => 'CBC',           'name' => 'Complete Blood Count',       'category' => 'laboratory', 'room' => 'laboratory',     'price' => 250.00,  'turnaround' => 4],
            ['code' => 'UA',            'name' => 'Urinalysis',                 'category' => 'laboratory', 'room' => 'laboratory',     'price' => 150.00,  'turnaround' => 2],
            ['code' => 'FECALYSIS',     'name' => 'Fecalysis / Stool Exam',     'category' => 'laboratory', 'room' => 'laboratory',     'price' => 150.00,  'turnaround' => 2],
            ['code' => 'BLOODTYPING',   'name' => 'Blood Typing (ABO+Rh)',      'category' => 'laboratory', 'room' => 'laboratory',     'price' => 150.00,  'turnaround' => 2],
            ['code' => 'FBS',           'name' => 'Fasting Blood Sugar',        'category' => 'laboratory', 'room' => 'laboratory',     'price' => 200.00,  'turnaround' => 4, 'fasting' => true],
            ['code' => 'RBS',           'name' => 'Random Blood Sugar',         'category' => 'laboratory', 'room' => 'laboratory',     'price' => 200.00,  'turnaround' => 4],
            ['code' => 'BUN',           'name' => 'Blood Urea Nitrogen',        'category' => 'laboratory', 'room' => 'laboratory',     'price' => 250.00,  'turnaround' => 4],
            ['code' => 'CREATININE',    'name' => 'Creatinine',                 'category' => 'laboratory', 'room' => 'laboratory',     'price' => 250.00,  'turnaround' => 4],
            ['code' => 'URICACID',      'name' => 'Uric Acid',                  'category' => 'laboratory', 'room' => 'laboratory',     'price' => 250.00,  'turnaround' => 4],
            ['code' => 'CHOLESTEROL',   'name' => 'Total Cholesterol',          'category' => 'laboratory', 'room' => 'laboratory',     'price' => 250.00,  'turnaround' => 4],
            ['code' => 'TRIGLYCERIDES', 'name' => 'Triglycerides',              'category' => 'laboratory', 'room' => 'laboratory',     'price' => 300.00,  'turnaround' => 4],
            ['code' => 'HDLLDL',        'name' => 'HDL / LDL Cholesterol',      'category' => 'laboratory', 'room' => 'laboratory',     'price' => 350.00,  'turnaround' => 4],
            ['code' => 'SGOT',          'name' => 'SGOT (AST)',                 'category' => 'laboratory', 'room' => 'laboratory',     'price' => 300.00,  'turnaround' => 4],
            ['code' => 'SGPT',          'name' => 'SGPT (ALT)',                 'category' => 'laboratory', 'room' => 'laboratory',     'price' => 300.00,  'turnaround' => 4],
            ['code' => 'HBSAG',         'name' => 'Hepatitis B Surface Antigen','category' => 'laboratory', 'room' => 'laboratory',     'price' => 400.00,  'turnaround' => 4],
            ['code' => 'VDRL',          'name' => 'VDRL (Syphilis Test)',       'category' => 'laboratory', 'room' => 'laboratory',     'price' => 300.00,  'turnaround' => 4],
            ['code' => 'PREGNANCY',     'name' => 'Pregnancy Test',             'category' => 'laboratory', 'room' => 'laboratory',     'price' => 200.00,  'turnaround' => 1],
            ['code' => 'DENGUE',        'name' => 'Dengue Duo Test',            'category' => 'laboratory', 'room' => 'laboratory',     'price' => 800.00,  'turnaround' => 2],
            ['code' => 'THYROID',       'name' => 'Thyroid Panel (TSH)',        'category' => 'laboratory', 'room' => 'laboratory',     'price' => 600.00,  'turnaround' => 8],

            // ── X-Ray & Ultrasound ───────────────────────
            ['code' => 'CXRPA',         'name' => 'Chest X-Ray PA View',        'category' => 'xray_utz',   'room' => 'xray_utz',       'price' => 350.00,  'turnaround' => 24],
            ['code' => 'UTZ_ABDOMEN',   'name' => 'Ultrasound - Whole Abdomen', 'category' => 'xray_utz',   'room' => 'xray_utz',       'price' => 800.00,  'turnaround' => 24],
            ['code' => 'UTZ_KUB',       'name' => 'Ultrasound - KUB',           'category' => 'xray_utz',   'room' => 'xray_utz',       'price' => 700.00,  'turnaround' => 24],
            ['code' => 'UTZ_PELVIS',    'name' => 'Ultrasound - Pelvis',        'category' => 'xray_utz',   'room' => 'xray_utz',       'price' => 700.00,  'turnaround' => 24],
            ['code' => 'ECG',           'name' => 'Electrocardiogram (ECG)',     'category' => 'xray_utz',   'room' => 'xray_utz',       'price' => 400.00,  'turnaround' => 4],

            // ── Drug Test ────────────────────────────────
            ['code' => 'DRUGTEST',      'name' => 'Drug Test (THC + MET)',      'category' => 'drug_test',  'room' => 'drug_test',      'price' => 450.00,  'turnaround' => 2],
            ['code' => 'DRUGTEST5',     'name' => 'Drug Test (5-Panel)',         'category' => 'drug_test',  'room' => 'drug_test',      'price' => 650.00,  'turnaround' => 2],

            // ── Consultation ─────────────────────────────
            ['code' => 'OPD',           'name' => 'OPD Consultation',           'category' => 'consultation','room' => 'interview_room', 'price' => 300.00,  'turnaround' => 0],
            ['code' => 'PE_CONSULT',    'name' => 'Pre-Employment Consultation', 'category' => 'consultation','room' => 'interview_room', 'price' => 200.00,  'turnaround' => 0],

            ['code' => 'ANNUAL_PE', 'name' => 'Annual Physical Exam Consultation', 'category' => 'consultation', 'room' => 'interview_room', 'price' => 200.00, 'turnaround' => 0],
            ['code' => 'EXIT_PE',   'name' => 'Exit Physical Exam Consultation',   'category' => 'consultation', 'room' => 'interview_room', 'price' => 200.00, 'turnaround' => 0],
            ['code' => 'FOLLOW_UP', 'name' => 'Follow-up Consultation',            'category' => 'consultation', 'room' => 'interview_room', 'price' => 150.00, 'turnaround' => 0],
        ];

        foreach ($services as $svc) {
            ServiceCatalog::firstOrCreate(
                ['service_code' => $svc['code']],
                [
                    'service_name'     => $svc['name'],
                    'category'         => $svc['category'],
                    'room'             => $svc['room'],
                    'base_price'       => $svc['price'],
                    'turnaround_hours' => $svc['turnaround'] ?? 4,
                    'requires_fasting' => $svc['fasting'] ?? false,
                    'is_active'        => true,
                ]
            );
        }

        $this->command->info('Service catalog seeded — ' . count($services) . ' services.');
    }
}
