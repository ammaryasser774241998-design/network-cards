<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DepositRequest; // في حال وجود جدول للطلبات

class DepositController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'reference_number' => 'required|string|max:100',
            'receipt_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $receiptPath = null;
        if ($request->hasFile('receipt_image')) {
            $receiptPath = $request->file('receipt_image')->store('receipts', 'public');
        }

        // تسجيل طلب التغذية لحين مراجعته أو تحديث الرصيد مباشرة
        // مثال تسجيل الطلب:
        /*
        DepositRequest::create([
            'user_id' => auth()->id(),
            'amount' => $request->amount,
            'reference_number' => $request->reference_number,
            'receipt_image' => $receiptPath,
            'status' => 'pending',
        ]);
        */

        return back()->with('success', 'تم إرسال طلب تغذية الحساب بنجاح، سيتم التأكد من التحويل وإضافة الرصيد.');
    }
}