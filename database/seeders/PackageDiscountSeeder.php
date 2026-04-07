<?php

namespace Database\Seeders;

use App\Models\PackageDiscount;
use Illuminate\Database\Seeder;

class PackageDiscountSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            [
                'package_key'         => 'pre_emp_pkg_1',
                'package_name'        => 'Pre-Employment Package 1',
                'service_codes'       => ['CBC', 'UA', 'CXRPA', 'PE_CONSULT'],
                'package_price'       => 0.00,   // Admin sets the actual discounted price
                'addon_drugtest_price'=> null,
                'is_active'           => true,
            ],
            [
                'package_key'         => 'pre_emp_pkg_2',
                'package_name'        => 'Pre-Employment Package 2',
                'service_codes'       => ['CBC', 'UA', 'CXRPA', 'DRUGTEST', 'PE_CONSULT'],
                'package_price'       => 0.00,
                'addon_drugtest_price'=> null,
                'is_active'           => true,
            ],
            [
                'package_key'         => 'pre_emp_pkg_3',
                'package_name'        => 'Pre-Employment Package 3',
                'service_codes'       => ['CBC', 'UA', 'CXRPA', 'FECALYSIS', 'PE_CONSULT'],
                'package_price'       => 0.00,
                // Set this to the discounted drug test price when added to this package
                'addon_drugtest_price'=> null,
                'is_active'           => true,
            ],
        ];

        foreach ($packages as $pkg) {
            PackageDiscount::updateOrCreate(
                ['package_key' => $pkg['package_key']],
                $pkg
            );
        }
    }
}
