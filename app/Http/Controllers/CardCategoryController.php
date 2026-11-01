<?php

namespace App\Http\Controllers;

use App\Models\CardCategory;
use Illuminate\Http\Request;

class CardCategoryController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
        ]);

        CardCategory::create([
            'name' => $request->name,
            'price' => $request->price,
        ]);

        return back()->with('success', 'تم إنشاء الفئة بنجاح.');
    }

    public function destroy(CardCategory $category)
    {
        $category->delete();

        return back()->with('success', 'تم حذف الفئة بنجاح.');
    }

    public function update(Request $request, CardCategory $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'cost_price' => 'required|numeric|min:0',
            'low_stock_threshold' => 'required|integer|min:0',
            'description' => 'nullable|string|max:2000',
            'wholesale_price' => 'nullable|numeric|min:0|lt:price',
            'wholesale_min_quantity' => 'required|integer|min:2|max:1000',
            'bonus_min_quantity' => 'required|integer|min:1|max:1000',
            'bonus_cards' => 'required|integer|min:0|max:100',
        ]);

        $category->update($validated);

        return back()->with('success', 'تم تحديث بيانات الفئة بنجاح.');
    }
}
