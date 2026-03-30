<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\LabTest;

class LabTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       $tests = [
            // ── CBC / HEMATOLOGY ────────────────────────
            ['code'=>'HGB',       'name'=>'Hemoglobin',        'cat'=>'hematology', 'unit'=>'g/dL',       'male'=>'135-175',      'female'=>'115-155',    'sort'=>1],
            ['code'=>'HCT',       'name'=>'Hematocrit',        'cat'=>'hematology', 'unit'=>'%',           'male'=>'40-52',        'female'=>'35-48',      'sort'=>2],
            ['code'=>'RBC',       'name'=>'RBC Count',         'cat'=>'hematology', 'unit'=>'x10 12/L',   'male'=>'4.5-5.2',      'female'=>'3.9-5.6',    'sort'=>3],
            ['code'=>'PLT',       'name'=>'Platelet Count',    'cat'=>'hematology', 'unit'=>'x10 9/L',    'general'=>'150-400',                           'sort'=>4],
            ['code'=>'WBC',       'name'=>'WBC Count',         'cat'=>'hematology', 'unit'=>'x10 9/L',    'general'=>'4.0-11.0',                          'sort'=>5],
            ['code'=>'SEG',       'name'=>'Segmenters',        'cat'=>'hematology', 'unit'=>'%',           'general'=>'50-70',                             'sort'=>6],
            ['code'=>'LYM',       'name'=>'Lymphocytes',       'cat'=>'hematology', 'unit'=>'%',           'general'=>'26-46',                             'sort'=>7],
            ['code'=>'MON',       'name'=>'Monocytes',         'cat'=>'hematology', 'unit'=>'%',           'general'=>'2-8',                               'sort'=>8],
            ['code'=>'EOS',       'name'=>'Eosinophils',       'cat'=>'hematology', 'unit'=>'%',           'general'=>'0-4',                               'sort'=>9],
            ['code'=>'BAS',       'name'=>'Basophils',         'cat'=>'hematology', 'unit'=>'%',           'general'=>'0-1',                               'sort'=>10],
            ['code'=>'MID',       'name'=>'Mid',               'cat'=>'hematology', 'unit'=>'%',           'general'=>'2-10',                              'sort'=>11],
            ['code'=>'BTYPE',     'name'=>'Blood Type',        'cat'=>'hematology', 'unit'=>'',            'text'=>true,                                   'sort'=>12],

            // ── BLOOD CHEMISTRY ──────────────────────────
            ['code'=>'FBS',       'name'=>'Fasting Blood Sugar',   'cat'=>'chemistry', 'unit'=>'mg/dL',   'general'=>'75-115',                            'sort'=>20],
            ['code'=>'RBS',       'name'=>'Random Blood Sugar',    'cat'=>'chemistry', 'unit'=>'mg/dL',   'general'=>'<200',                              'sort'=>21],
            ['code'=>'BUN',       'name'=>'Blood Urea Nitrogen',   'cat'=>'chemistry', 'unit'=>'mg/dL',   'general'=>'<33.1',                             'sort'=>22],
            ['code'=>'CREAT',     'name'=>'Creatinine',            'cat'=>'chemistry', 'unit'=>'mg/dL',   'male'=>'0.6-1.4',      'female'=>'0.6-1.2',    'sort'=>23],
            ['code'=>'UA_CHEM',   'name'=>'Uric Acid',             'cat'=>'chemistry', 'unit'=>'mg/dL',   'male'=>'3.5-7.5',      'female'=>'2.6-6.0',    'sort'=>24],
            ['code'=>'SGPT',      'name'=>'SGPT (ALT)',            'cat'=>'chemistry', 'unit'=>'U/L',     'male'=>'<41',          'female'=>'<31',         'sort'=>25],
            ['code'=>'SGOT',      'name'=>'SGOT (AST)',            'cat'=>'chemistry', 'unit'=>'U/L',     'male'=>'<37',          'female'=>'<31',         'sort'=>26],
            ['code'=>'CHOL',      'name'=>'Total Cholesterol',     'cat'=>'chemistry', 'unit'=>'mg/dL',   'general'=>'<200',                              'sort'=>27],
            ['code'=>'TRIG',      'name'=>'Triglycerides',         'cat'=>'chemistry', 'unit'=>'mg/dL',   'general'=>'<200',                              'sort'=>28],
            ['code'=>'HDL',       'name'=>'HDL Cholesterol',       'cat'=>'chemistry', 'unit'=>'mg/dL',   'general'=>'>35',                               'sort'=>29],
            ['code'=>'LDL',       'name'=>'LDL Cholesterol',       'cat'=>'chemistry', 'unit'=>'mg/dL',   'general'=>'<110',                              'sort'=>30],
            ['code'=>'VLDL',      'name'=>'VLDL',                  'cat'=>'chemistry', 'unit'=>'mg/dL',   'general'=>'<5-40',                             'sort'=>31],

            // ── URINALYSIS ───────────────────────────────
            ['code'=>'UA_COLOR',  'name'=>'Urine Color',           'cat'=>'urinalysis','unit'=>'',        'text'=>true,                                   'sort'=>40],
            ['code'=>'UA_TRANS',  'name'=>'Transparency',          'cat'=>'urinalysis','unit'=>'',        'text'=>true,                                   'sort'=>41],
            ['code'=>'UA_PH',     'name'=>'pH',                    'cat'=>'urinalysis','unit'=>'',        'general'=>'5.0-8.0',                           'sort'=>42],
            ['code'=>'UA_SG',     'name'=>'Specific Gravity',      'cat'=>'urinalysis','unit'=>'',        'general'=>'1.005-1.030',                       'sort'=>43],
            ['code'=>'UA_SUGAR',  'name'=>'Sugar',                 'cat'=>'urinalysis','unit'=>'',        'text'=>true,   'general'=>'Negative',          'sort'=>44],
            ['code'=>'UA_PRO',    'name'=>'Protein',               'cat'=>'urinalysis','unit'=>'',        'text'=>true,   'general'=>'Negative',          'sort'=>45],
            ['code'=>'UA_PUS',    'name'=>'Pus Cells',             'cat'=>'urinalysis','unit'=>'/hpf',    'general'=>'0-5',                               'sort'=>46],
            ['code'=>'UA_RBC',    'name'=>'RBC',                   'cat'=>'urinalysis','unit'=>'/hpf',    'general'=>'0-2',                               'sort'=>47],
            ['code'=>'UA_EPI',    'name'=>'Epithelial Cells',      'cat'=>'urinalysis','unit'=>'',        'text'=>true,                                   'sort'=>48],
            ['code'=>'UA_MUC',    'name'=>'Mucus Threads',         'cat'=>'urinalysis','unit'=>'',        'text'=>true,                                   'sort'=>49],
            ['code'=>'UA_AMO',    'name'=>'Amorphous Substance',   'cat'=>'urinalysis','unit'=>'',        'text'=>true,                                   'sort'=>50],
            ['code'=>'UA_BAC',    'name'=>'Bacteria',              'cat'=>'urinalysis','unit'=>'',        'text'=>true,                                   'sort'=>51],
            ['code'=>'UA_CRY',    'name'=>'Crystals',              'cat'=>'urinalysis','unit'=>'',        'text'=>true,                                   'sort'=>52],
            ['code'=>'UA_OTH',    'name'=>'Others',                'cat'=>'urinalysis','unit'=>'',        'text'=>true,                                   'sort'=>53],

            // ── STOOL / FECALYSIS ────────────────────────
            ['code'=>'ST_COL',    'name'=>'Color',                 'cat'=>'stool',     'unit'=>'',        'text'=>true,                                   'sort'=>60],
            ['code'=>'ST_CON',    'name'=>'Consistency',           'cat'=>'stool',     'unit'=>'',        'text'=>true,                                   'sort'=>61],
            ['code'=>'ST_PUS',    'name'=>'Pus Cells',             'cat'=>'stool',     'unit'=>'/hpf',    'general'=>'0-2',                               'sort'=>62],
            ['code'=>'ST_RBC',    'name'=>'Red Blood Cells',       'cat'=>'stool',     'unit'=>'/hpf',    'general'=>'0',                                 'sort'=>63],
            ['code'=>'ST_OVA',    'name'=>'Ova/Parasites',         'cat'=>'stool',     'unit'=>'',        'text'=>true,   'general'=>'Negative',          'sort'=>64],

            // ── SEROLOGY / IMMUNOLOGY ────────────────────
            ['code'=>'HBSAG',     'name'=>'Hepatitis B sAg',       'cat'=>'serology',  'unit'=>'',        'text'=>true,   'general'=>'Non-Reactive',      'sort'=>70],
            ['code'=>'VDRL',      'name'=>'VDRL (Syphilis)',        'cat'=>'serology',  'unit'=>'',        'text'=>true,   'general'=>'Non-Reactive',      'sort'=>71],
            ['code'=>'HIV1',      'name'=>'HIV 1 Ab',              'cat'=>'serology',  'unit'=>'',        'text'=>true,   'general'=>'Non-Reactive',      'sort'=>72],
            ['code'=>'HIV2',      'name'=>'HIV 2 Ab',              'cat'=>'serology',  'unit'=>'',        'text'=>true,   'general'=>'Non-Reactive',      'sort'=>73],
            ['code'=>'PREG',      'name'=>'Pregnancy Test',        'cat'=>'serology',  'unit'=>'',        'text'=>true,                                   'sort'=>74],
            ['code'=>'BTYPE2',    'name'=>'Blood Type',            'cat'=>'serology',  'unit'=>'',        'text'=>true,                                   'sort'=>75],
            ['code'=>'PSA',       'name'=>'Prostatic Specific Antigen', 'cat'=>'serology','unit'=>'ng/mL','general'=>'0-4',                               'sort'=>76],
            ['code'=>'DNG_NS1',   'name'=>'Dengue NS1 Ag',         'cat'=>'serology',  'unit'=>'',        'text'=>true,                                   'sort'=>77],
            ['code'=>'DNG_IGG',   'name'=>'Dengue IgG',            'cat'=>'serology',  'unit'=>'',        'text'=>true,                                   'sort'=>78],
            ['code'=>'DNG_IGM',   'name'=>'Dengue IgM',            'cat'=>'serology',  'unit'=>'',        'text'=>true,                                   'sort'=>79],
        ];

        foreach ($tests as $t) {
            LabTest::firstOrCreate(
                ['test_code' => $t['code']],
                [
                    'test_name'            => $t['name'],
                    'category'             => $t['cat'],
                    'unit'                 => $t['unit'] ?? null,
                    'normal_range_male'    => $t['male']    ?? null,
                    'normal_range_female'  => $t['female']  ?? null,
                    'normal_range_general' => $t['general'] ?? null,
                    'is_text_result'       => $t['text']    ?? false,
                    'sort_order'           => $t['sort'],
                    'is_active'            => true,
                ]
            );
        }

        $this->command->info('Lab tests seeded — '.count($tests).' tests.');
    }
}
