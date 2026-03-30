<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceCatalog;
use App\Models\ServicePriceHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ServiceCatalogController extends Controller
{
    public function index(Request $request)
    {
        $query = ServiceCatalog::with('changedBy');

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('is_active', $request->status === 'active');
        }

        $services = $query->orderBy('category')->orderBy('service_name')
            ->paginate(20)->withQueryString();

        $transformed = $services->getCollection()->map(fn($s) => [
            'id'               => $s->id,
            'service_code'     => $s->service_code,
            'service_name'     => $s->service_name,
            'category'         => $s->category,
            'room'             => $s->room,
            'base_price'       => $s->base_price,
            'formatted_price'  => $s->formatted_price,
            'turnaround_hours' => $s->turnaround_hours,
            'requires_fasting' => $s->requires_fasting,
            'is_active'        => $s->is_active,
            'price_changed_by' => $s->changedBy?->name,
            'price_changed_at' => $s->price_changed_at?->format('M d, Y h:i A'),
        ]);

        $services->setCollection($transformed);

        return inertia('Admin/Services/Index', [
            'services' => $services,
            'filters'  => $request->only(['search', 'category', 'status']),
            'summary'  => [
                'total'    => ServiceCatalog::count(),
                'active'   => ServiceCatalog::where('is_active', true)->count(),
                'inactive' => ServiceCatalog::where('is_active', false)->count(),
            ],
        ]);
    }

    public function create()
    {
        return inertia('Admin/Services/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'service_code'     => ['required', 'string', 'max:20', 'unique:service_catalog,service_code'],
            'service_name'     => ['required', 'string', 'max:150'],
            'category'         => ['required', 'in:laboratory,xray_utz,drug_test,consultation,procedure,other'],
            'room'             => ['required', 'in:laboratory,xray_utz,drug_test,interview_room,none'],
            'base_price'       => ['required', 'numeric', 'min:0'],
            'description'      => ['nullable', 'string'],
            'requires_fasting' => ['boolean'],
            'turnaround_hours' => ['required', 'integer', 'min:0'],
        ]);

        ServiceCatalog::create([
            ...$validated,
            'is_active' => true,
        ]);

        return redirect()->route('admin.services.index')
            ->with('success', "Service '{$validated['service_name']}' added to catalog.");
    }

    public function edit(ServiceCatalog $service)
    {
        $history = $service->priceHistory()
            ->with('changedBy')
            ->limit(10)
            ->get()
            ->map(fn($h) => [
                'id'         => $h->id,
                'old_price'  => '₱ ' . number_format($h->old_price, 2),
                'new_price'  => '₱ ' . number_format($h->new_price, 2),
                'changed_by' => $h->changedBy?->name ?? 'System',
                'changed_at' => $h->changed_at->format('M d, Y h:i A'),
                'reason'     => $h->reason,
            ]);

        return inertia('Admin/Services/Edit', [
            'service' => [
                'id'               => $service->id,
                'service_code'     => $service->service_code,
                'service_name'     => $service->service_name,
                'category'         => $service->category,
                'room'             => $service->room,
                'base_price'       => $service->base_price,
                'description'      => $service->description,
                'requires_fasting' => $service->requires_fasting,
                'turnaround_hours' => $service->turnaround_hours,
                'is_active'        => $service->is_active,
            ],
            'priceHistory' => $history,
        ]);
    }

    public function update(Request $request, ServiceCatalog $service)
    {
        $validated = $request->validate([
            'service_name'     => ['required', 'string', 'max:150'],
            'category'         => ['required', 'in:laboratory,xray_utz,drug_test,consultation,procedure,other'],
            'room'             => ['required', 'in:laboratory,xray_utz,drug_test,interview_room,none'],
            'base_price'       => ['required', 'numeric', 'min:0'],
            'description'      => ['nullable', 'string'],
            'requires_fasting' => ['boolean'],
            'turnaround_hours' => ['required', 'integer', 'min:0'],
            'is_active'        => ['boolean'],
            'price_reason'     => ['nullable', 'string', 'max:255'],
        ]);

        // If price changed, store reason in history
        if ($service->base_price != $validated['base_price'] && $request->filled('price_reason')) {
            ServicePriceHistory::where('service_id', $service->id)
                ->latest()
                ->limit(1)
                ->update(['reason' => $validated['price_reason']]);
        }

        $service->update($validated);

        return redirect()->route('admin.services.index')
            ->with('success', "'{$service->service_name}' updated successfully.");
    }

    public function toggleActive(ServiceCatalog $service)
    {
        $service->update(['is_active' => !$service->is_active]);
        $status = $service->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "'{$service->service_name}' {$status}.");
    }
}
