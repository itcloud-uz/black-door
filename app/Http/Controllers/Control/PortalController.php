<?php

declare(strict_types=1);

namespace App\Http\Controllers\Control;

use App\Http\Controllers\Controller;
use App\Models\Control\ClientRequest;
use App\Models\Control\Product;
use App\Models\Control\TariffPlan;
use Illuminate\Http\Request;

class PortalController extends Controller
{
    public function showRequestForm()
    {
        $products = Product::where('is_active', true)->with('tariffPlans')->get();
        return view('control.portal.request', compact('products'));
    }

    public function submitRequest(Request $request)
    {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'contact_name' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'product_id' => 'required|exists:control_products,id',
            'tariff_plan_id' => 'required|exists:control_tariff_plans,id',
            'notes' => 'nullable|string',
        ]);

        ClientRequest::create($request->all());

        return back()->with('success', 'Arizangiz qabul qilindi. Tez orada sotuv menejerlarimiz siz bilan bog\'lanishadi!');
    }

    public function listRequests()
    {
        $requests = ClientRequest::with(['product', 'tariffPlan'])->orderBy('created_at', 'desc')->get();
        return view('control.requests.index', compact('requests'));
    }

    public function updateRequestStatus(Request $request, ClientRequest $clientRequest)
    {
        $request->validate([
            'status' => 'required|string|in:pending,contacted,approved,rejected',
        ]);

        $clientRequest->update([
            'status' => $request->status,
        ]);

        return back()->with('success', 'Ariza holati o\'zgartirildi.');
    }
}
