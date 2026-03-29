<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\QueueCounter;
class QueueCounterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $counters = [
            [
                'counter_name'   => 'Counter 1 — OPD',
                'counter_code'   => 'A',
                'assigned_types' => ['opd', 'follow_up'],
                'is_active'      => true,
            ],
            [
                'counter_name'   => 'Counter 2 — Pre-Employment',
                'counter_code'   => 'B',
                'assigned_types' => ['pre_employment'],
                'is_active'      => true,
            ],
            [
                'counter_name'   => 'Counter 3 — Lab Only',
                'counter_code'   => 'C',
                'assigned_types' => ['lab_only'],
                'is_active'      => true,
            ],
        ];

        foreach ($counters as $counter) {
            QueueCounter::firstOrCreate(
                ['counter_code' => $counter['counter_code']],
                $counter
            );
        }

        $this->command->info('Queue counters seeded.');
    }
}
