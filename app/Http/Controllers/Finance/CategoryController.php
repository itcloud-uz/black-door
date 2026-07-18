<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\TransactionCategory;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = TransactionCategory::with('children')
            ->whereNull('parent_id')
            ->orderBy('type')
            ->orderBy('name')
            ->get();
            
        return view('finance.categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:income,expense',
            'parent_id' => 'nullable|exists:transaction_categories,id',
        ]);

        $category = TransactionCategory::create([
            'name' => $request->name,
            'type' => $request->type,
            'parent_id' => $request->parent_id,
            'is_active' => true,
        ]);

        AuditLogger::log('create_category', $category, null, $category->toArray());

        return redirect()->route('finance.categories.index')->with('success', 'Kategoriya muvaffaqiyatli qo\'shildi.');
    }

    public function update(Request $request, TransactionCategory $category)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:income,expense',
            'parent_id' => 'nullable|exists:transaction_categories,id',
        ]);

        $oldValues = $category->toArray();
        $category->update([
            'name' => $request->name,
            'type' => $request->type,
            'parent_id' => $request->parent_id,
        ]);

        AuditLogger::log('update_category', $category, $oldValues, $category->toArray());

        return redirect()->route('finance.categories.index')->with('success', 'Kategoriya muvaffaqiyatli yangilandi.');
    }

    public function destroy(TransactionCategory $category)
    {
        if ($category->children()->count() > 0) {
            return back()->withErrors(['error' => 'Kategoriyani o\'chirish mumkin emas: uning ostida boshqa kategoriyalar bor.']);
        }

        if ($category->transactions()->count() > 0) {
            return back()->withErrors(['error' => 'Kategoriyani o\'chirish mumkin emas: unga bog\'liq tranzaksiyalar mavjud.']);
        }

        $oldValues = $category->toArray();
        $category->delete();

        AuditLogger::log('delete_category', $category, $oldValues);

        return redirect()->route('finance.categories.index')->with('success', 'Kategoriya muvaffaqiyatli o\'chirildi.');
    }
}
