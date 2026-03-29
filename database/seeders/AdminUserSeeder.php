<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@clinichub.local'],
            [
                'name'                => 'System Administrator',
                'password'            => Hash::make('Admin@1234'),
                'employee_id'         => 'ADMIN-001',
                'department'          => 'admin',
                'is_active'           => true,
                'must_change_password' => true,
            ]
        );

        $admin->assignRole('admin');

        $this->command->info(' Admin user seeded: admin@clinichub.local / Admin@1234');
    }
}
