<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
     public function index(Request $request)
    {
        $user = $request->user()->load('roles');
        $role = $user->getRoleNames()->first();

        $dashboardMap = [
            'admin'           => 'Dashboard/Admin',
            'receptionist'    => 'Dashboard/Receptionist',
            'nurse'           => 'Dashboard/Nurse',
            'doctor'          => 'Dashboard/Doctor',
            'lab_technician'  => 'Dashboard/Laboratory',
            'xray_tech'       => 'Dashboard/XRay',
            'drug_test_staff' => 'Dashboard/DrugTest',
        ];

        $component = $dashboardMap[$role] ?? 'Dashboard/Admin';

        return inertia($component, [
            'user' => $user,
            'role' => $role,
        ]);
    }
}
