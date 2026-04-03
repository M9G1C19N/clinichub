<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Esignature;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EsignatureController extends Controller
{
    public function index()
    {
        $users = User::with('esignature')
            ->where('is_active', true)
            ->whereNotNull('department')
            ->orderBy('department')
            ->orderBy('name')
            ->get()
            ->map(fn($u) => [
                'id'              => $u->id,
                'name'            => $u->name,
                'department'      => $u->department,
                'prc_number'      => $u->prc_number,
                'has_signature'   => $u->esignature !== null,
                'esignature' => $u->esignature ? [
                    'id'              => $u->esignature->id,
                    'title'           => $u->esignature->title,
                    'license_number'  => $u->esignature->license_number,
                    'ptr_number'      => $u->esignature->ptr_number,
                    'signature_url'   => $u->esignature->signature_url,
                    'is_active'       => $u->esignature->is_active,
                ] : null,
            ]);

        return inertia('Admin/Esignatures', ['users' => $users]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id'        => ['required', 'exists:users,id'],
            'title'          => ['nullable', 'string', 'max:150'],
            'license_number' => ['nullable', 'string', 'max:80'],
            'ptr_number'     => ['nullable', 'string', 'max:80'],
            'signature'      => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'is_active'      => ['boolean'],
        ]);

        $existing = Esignature::where('user_id', $validated['user_id'])->first();
        $data = [
            'user_id'        => $validated['user_id'],
            'title'          => $validated['title'] ?? null,
            'license_number' => $validated['license_number'] ?? null,
            'ptr_number'     => $validated['ptr_number'] ?? null,
            'is_active'      => $validated['is_active'] ?? true,
        ];

        if ($request->hasFile('signature')) {
            // Delete old signature if replacing
            if ($existing?->signature_path) {
                Storage::disk('public')->delete($existing->signature_path);
            }
            $data['signature_path'] = $request->file('signature')
                ->store('signatures', 'public');
        }

        Esignature::updateOrCreate(
            ['user_id' => $validated['user_id']],
            $data
        );

        return back()->with('success', 'Signature profile saved.');
    }

    public function destroy(Esignature $esignature)
    {
        if ($esignature->signature_path) {
            Storage::disk('public')->delete($esignature->signature_path);
        }
        $esignature->delete();

        return back()->with('success', 'Signature removed.');
    }

    public function toggleActive(Esignature $esignature)
    {
        $esignature->update(['is_active' => ! $esignature->is_active]);
        return back()->with('success', 'Status updated.');
    }
}
