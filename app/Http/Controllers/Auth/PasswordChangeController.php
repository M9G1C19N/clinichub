<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PasswordChangeController extends Controller
{
    public function show()
    {
        return inertia('Auth/ChangePassword');
    }

    public function update(Request $request)
    {
        $request->validate([
            'password' => ['required', 'min:8', 'confirmed'],
        ]);

        $request->user()->update([
            'password'             => Hash::make($request->password),
            'must_change_password' => false,
        ]);

        return redirect()->route('dashboard')
            ->with('success', 'Password updated successfully!');
    }
}
