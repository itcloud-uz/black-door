<?php

declare(strict_types=1);

namespace App\Http\Controllers\Control;

use App\Http\Controllers\Controller;
use App\Models\Control\Client;
use App\Models\Control\License;
use App\Models\Control\Product;
use App\Models\Control\TariffPlan;
use App\Models\Control\LicensePayment;
use App\Models\Control\ControlAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ClientController extends Controller
{
    public function index()
    {
        $clients = Client::withCount('licenses')->get();
        return view('control.clients.index', compact('clients'));
    }

    public function create()
    {
        return view('control.clients.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'contact_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:255',
            'telegram' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        Client::create($request->all());

        return redirect()->route('control.clients.index')->with('success', 'Mijoz muvaffaqiyatli yaratildi.');
    }

    public function show(Client $client)
    {
        $client->load(['licenses.product', 'licenses.tariffPlan', 'licenses.installations']);
        $products = Product::where('is_active', true)->get();
        $plans = TariffPlan::where('is_active', true)->get();
        return view('control.clients.show', compact('client', 'products', 'plans'));
    }

    public function storeLicense(Request $request, Client $client)
    {
        $request->validate([
            'product_id' => 'required|exists:control_products,id',
            'tariff_plan_id' => 'required|exists:control_tariff_plans,id',
            'starts_at' => 'required|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
            'activation_limit' => 'required|integer|min:1',
        ]);

        $plan = TariffPlan::findOrFail($request->tariff_plan_id);
        
        $key = 'BD-' . 
            strtoupper(Str::random(4)) . '-' . 
            strtoupper(Str::random(4)) . '-' . 
            strtoupper(Str::random(4)) . '-' . 
            strtoupper(Str::random(4));

        $license = License::create([
            'client_id' => $client->id,
            'product_id' => $request->product_id,
            'tariff_plan_id' => $request->tariff_plan_id,
            'license_key' => $key,
            'status' => 'awaiting_activation',
            'starts_at' => $request->starts_at,
            'expires_at' => $request->expires_at,
            'activation_limit' => $request->activation_limit,
        ]);

        ControlAuditLog::create([
            'license_id' => $license->id,
            'action' => 'created',
            'new_values' => $license->toArray(),
            'performed_by' => auth()->user()?->name ?? 'Admin',
        ]);

        return back()->with('success', 'Litsenziya muvaffaqiyatli yaratildi. Kalit: ' . $key);
    }

    public function toggleLicenseStatus(License $license)
    {
        $oldStatus = $license->status;
        $newStatus = $oldStatus === 'suspended' ? 'active' : 'suspended';
        
        $license->update(['status' => $newStatus]);

        ControlAuditLog::create([
            'license_id' => $license->id,
            'action' => 'status_changed',
            'old_values' => ['status' => $oldStatus],
            'new_values' => ['status' => $newStatus],
            'performed_by' => auth()->user()?->name ?? 'Admin',
        ]);

        return back()->with('success', 'Litsenziya holati o\'zgartirildi.');
    }

    public function storePayment(Request $request, License $license)
    {
        $request->validate([
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|in:USD,UZS',
            'payment_method' => 'required|string|in:cash,bank,card',
            'notes' => 'nullable|string',
        ]);

        $cents = (int)round((float)$request->amount * 100);

        LicensePayment::create([
            'license_id' => $license->id,
            'payment_date' => $request->payment_date,
            'amount' => $cents,
            'currency' => $request->currency,
            'payment_method' => $request->payment_method,
            'notes' => $request->notes,
        ]);

        return back()->with('success', 'To\'lov muvaffaqiyatli qayd etildi.');
    }

    public function edit(Client $client)
    {
        return view('control.clients.edit', compact('client'));
    }

    public function update(Request $request, Client $client)
    {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'contact_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:255',
            'telegram' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $client->update($request->all());

        return redirect()->route('control.clients.show', $client->id)->with('success', 'Mijoz ma\'lumotlari muvaffaqiyatli yangilandi.');
    }

    public function destroy(Client $client)
    {
        $client->delete();
        return redirect()->route('control.clients.index')->with('success', 'Mijoz muvaffaqiyatli o\'chirildi.');
    }
}
