<?php

declare(strict_types=1);

namespace App\Http\Controllers\Control;

use App\Http\Controllers\Controller;
use App\Models\Control\Product;
use App\Models\Control\ProductVersion;
use App\Models\Control\TariffPlan;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::withCount(['versions', 'tariffPlans'])->get();
        return view('control.products.index', compact('products'));
    }

    public function create()
    {
        return view('control.products.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:control_products,code',
            'description' => 'nullable|string',
        ]);

        Product::create($request->all());

        return redirect()->route('control.products.index')->with('success', 'Mahsulot muvaffaqiyatli katalogga qo\'shildi.');
    }

    public function show(Product $product)
    {
        $product->load(['versions', 'tariffPlans']);
        return view('control.products.show', compact('product'));
    }

    public function storeVersion(Request $request, Product $product)
    {
        $request->validate([
            'version' => 'required|string|max:255',
            'release_date' => 'required|date',
            'release_notes' => 'nullable|string',
            'checksum' => 'nullable|string|max:255',
            'download_path' => 'nullable|string|max:255',
        ]);

        ProductVersion::create([
            'product_id' => $product->id,
            'version' => $request->version,
            'release_date' => $request->release_date,
            'release_notes' => $request->release_notes,
            'checksum' => $request->checksum,
            'download_path' => $request->download_path,
        ]);

        return back()->with('success', 'Mahsulot versiyasi muvaffaqiyatli saqlandi.');
    }

    public function storePlan(Request $request, Product $product)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255',
            'duration_days' => 'nullable|integer|min:1',
            'price' => 'required|numeric|min:0',
            'currency' => 'required|string|in:USD,UZS',
            'max_users' => 'required|integer|min:1',
            'max_objects' => 'required|integer|min:1',
            'features' => 'nullable|array',
        ]);

        $cents = (int)round((float)$request->price * 100);

        // Map feature flags
        $features = [
            'mobile_api' => $request->has('features.mobile_api'),
            'reports' => $request->has('features.reports'),
            'real_time' => $request->has('features.real_time'),
        ];

        TariffPlan::create([
            'product_id' => $product->id,
            'name' => $request->name,
            'code' => $request->code,
            'duration_days' => $request->duration_days ? (int)$request->duration_days : null,
            'price' => $cents,
            'currency' => $request->currency,
            'max_users' => (int)$request->max_users,
            'max_objects' => (int)$request->max_objects,
            'features' => $features,
            'is_active' => true,
        ]);

        return back()->with('success', 'Tarif rejasi muvaffaqiyatli yaratildi.');
    }

    public function edit(Product $product)
    {
        return view('control.products.edit', compact('product'));
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:control_products,code,' . $product->id,
            'description' => 'nullable|string',
        ]);

        $product->update($request->all());

        return redirect()->route('control.products.show', $product->id)->with('success', 'Mahsulot muvaffaqiyatli yangilandi.');
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return redirect()->route('control.products.index')->with('success', 'Mahsulot muvaffaqiyatli o\'chirildi.');
    }
}
