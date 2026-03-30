<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    use AuthorizesRequests;

    // ── LIST ──────────────────────────────────────────

   public function index(Request $request)
{
    $query = User::with('roles');

    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('employee_id', 'like', "%{$search}%");
        });
    }

    if ($request->filled('role') && $request->role !== 'all') {
        $query->whereHas('roles', fn($q) => $q->where('name', $request->role));
    }

    if ($request->filled('status') && $request->status !== 'all') {
        $query->where('is_active', $request->status === 'active');
    }

    if ($request->filled('department') && $request->department !== 'all') {
        $query->where('department', $request->department);
    }

    $users = $query->orderBy('name')->paginate(15)->withQueryString();

    $transformed = $users->getCollection()->map(fn($u) => [
        'id'                   => $u->id,
        'name'                 => $u->name,
        'email'                => $u->email,
        'employee_id'          => $u->employee_id,
        'role'                 => $u->getRoleNames()->first(),
        'department'           => $u->department,
        'is_active'            => $u->is_active,
        'photo_path'           => $u->photo_path,
        'created_at'           => $u->created_at->format('M d, Y'),
        'must_change_password' => $u->must_change_password,
    ]);

    $users->setCollection($transformed);

    return inertia('Admin/Users/Index', [
        'users'   => $users,
        'roles'   => Role::orderBy('name')->pluck('name'),
        'filters' => $request->only(['search', 'role', 'status', 'department']),
        'total'   => User::count(),
    ]);
}

    // ── CREATE FORM ───────────────────────────────────

    public function create()
    {
        return inertia('Admin/Users/Create', [
            'roles' => Role::orderBy('name')->pluck('name'),
        ]);
    }

    // ── STORE ─────────────────────────────────────────

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:150'],
            'email'          => ['required', 'email', 'unique:users,email'],
            'employee_id'    => ['nullable', 'string', 'max:30', 'unique:users,employee_id'],
            'role'           => ['required', 'exists:roles,name'],
            'department'     => ['nullable', 'in:laboratory,xray_utz,drug_test,reception,nursing,admin,doctor'],
            'specialization' => ['nullable', 'string', 'max:100'],
            'prc_number'     => ['nullable', 'string', 'max:50'],
            'ptr_number'     => ['nullable', 'string', 'max:50'],
            'password'       => ['required', Password::min(8)->mixedCase()->numbers()],
        ]);

        $user = User::create([
            'name'                 => $validated['name'],
            'email'                => $validated['email'],
            'employee_id'          => $validated['employee_id'] ?? null,
            'department'           => $validated['department'] ?? null,
            'specialization'       => $validated['specialization'] ?? null,
            'prc_number'           => $validated['prc_number'] ?? null,
            'ptr_number'           => $validated['ptr_number'] ?? null,
            'password'             => Hash::make($validated['password']),
            'is_active'            => true,
            'must_change_password' => true,
        ]);

        $user->assignRole($validated['role']);

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Staff account created for {$user->name}. They must change their password on first login.");
    }

    // ── EDIT FORM ─────────────────────────────────────

    public function edit(User $user)
    {
        return inertia('Admin/Users/Edit', [
            'staff' => [
                'id'             => $user->id,
                'name'           => $user->name,
                'email'          => $user->email,
                'employee_id'    => $user->employee_id,
                'role'           => $user->getRoleNames()->first(),
                'department'     => $user->department,
                'specialization' => $user->specialization,
                'prc_number'     => $user->prc_number,
                'ptr_number'     => $user->ptr_number,
                'is_active'      => $user->is_active,
                'photo_path'     => $user->photo_path,
                'must_change_password' => $user->must_change_password,
                'created_at'     => $user->created_at->format('M d, Y'),
            ],
            'roles' => Role::orderBy('name')->pluck('name'),
        ]);
    }

    // ── UPDATE ────────────────────────────────────────

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:150'],
            'email'          => ['required', 'email', "unique:users,email,{$user->id}"],
            'employee_id'    => ['nullable', 'string', 'max:30', "unique:users,employee_id,{$user->id}"],
            'role'           => ['required', 'exists:roles,name'],
            'department'     => ['nullable', 'in:laboratory,xray_utz,drug_test,reception,nursing,admin,doctor'],
            'specialization' => ['nullable', 'string', 'max:100'],
            'prc_number'     => ['nullable', 'string', 'max:50'],
            'ptr_number'     => ['nullable', 'string', 'max:50'],
            'is_active'      => ['boolean'],
        ]);

        $user->update([
            'name'           => $validated['name'],
            'email'          => $validated['email'],
            'employee_id'    => $validated['employee_id'] ?? null,
            'department'     => $validated['department'] ?? null,
            'specialization' => $validated['specialization'] ?? null,
            'prc_number'     => $validated['prc_number'] ?? null,
            'ptr_number'     => $validated['ptr_number'] ?? null,
            'is_active'      => $validated['is_active'],
        ]);

        // Update role
        $user->syncRoles([$validated['role']]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Staff account for {$user->name} updated successfully.");
    }

    // ── RESET PASSWORD ────────────────────────────────

    public function resetPassword(Request $request, User $user)
    {
        $request->validate([
            'password' => ['required', Password::min(8)->mixedCase()->numbers()],
        ]);

        $user->update([
            'password'             => Hash::make($request->password),
            'must_change_password' => true,
        ]);

        return back()->with('success', "Password reset for {$user->name}. They must change it on next login.");
    }

    // ── TOGGLE ACTIVE ─────────────────────────────────

   public function toggleActive(User $user)
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $user->update(['is_active' => !$user->is_active]);

        $status = $user->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Account {$status} for {$user->name}.");
    }

    // ── DELETE (soft) ─────────────────────────────────

   public function destroy(User $user)
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', "{$user->name}'s account has been removed.");
    }
}
