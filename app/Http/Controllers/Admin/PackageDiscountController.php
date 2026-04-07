<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PackageDiscount;
use App\Models\ServiceCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PackageDiscountController extends Controller
{
    public function index()
    {
        $servicePrices = ServiceCatalog::active()
            ->get()
            ->keyBy('service_code')
            ->map(fn($s) => [
                'name'  => $s->service_name,
                'price' => (float) $s->base_price,
            ]);

        $packages = PackageDiscount::orderBy('id')->get()->map(function ($pkg) use ($servicePrices) {
            $services = collect($pkg->service_codes)->map(fn($code) => [
                'code'  => $code,
                'name'  => $servicePrices->get($code)['name'] ?? $code,
                'price' => $servicePrices->get($code)['price'] ?? 0.0,
            ]);

            $catalogTotal = $services->sum('price');
            $packagePrice = (float) $pkg->package_price;

            return [
                'id'                   => $pkg->id,
                'package_key'          => $pkg->package_key,
                'package_name'         => $pkg->package_name,
                'service_codes'        => $pkg->service_codes,
                'services'             => $services->values(),
                'catalog_total'        => $catalogTotal,
                'package_price'        => $packagePrice,
                'discount_amount'      => max(0, $catalogTotal - $packagePrice),
                'addon_drugtest_price' => $pkg->addon_drugtest_price !== null ? (float) $pkg->addon_drugtest_price : null,
                'is_active'            => $pkg->is_active,
                'updated_by'           => $pkg->updatedBy?->name,
                'updated_at'           => $pkg->updated_at?->format('M d, Y h:i A'),
            ];
        });

        // Drug test catalog price for reference in the UI
        $drugtestCatalogPrice = $servicePrices->get('DRUGTEST')['price'] ?? null;

        return inertia('Admin/PackageDiscounts/Index', [
            'packages'             => $packages,
            'drugtestCatalogPrice' => $drugtestCatalogPrice,
        ]);
    }

    public function update(Request $request, PackageDiscount $packageDiscount)
    {
        $validated = $request->validate([
            'package_price'        => ['required', 'numeric', 'min:0'],
            'addon_drugtest_price' => ['nullable', 'numeric', 'min:0'],
            'is_active'            => ['boolean'],
        ]);

        $packageDiscount->update([
            'package_price'        => $validated['package_price'],
            'addon_drugtest_price' => $validated['addon_drugtest_price'] ?? null,
            'is_active'            => $validated['is_active'] ?? $packageDiscount->is_active,
            'updated_by'           => Auth::id(),
        ]);

        return back()->with('success', "{$packageDiscount->package_name} updated successfully.");
    }

    public function toggleActive(PackageDiscount $packageDiscount)
    {
        $packageDiscount->update([
            'is_active'  => ! $packageDiscount->is_active,
            'updated_by' => Auth::id(),
        ]);

        $state = $packageDiscount->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "{$packageDiscount->package_name} {$state}.");
    }
}
