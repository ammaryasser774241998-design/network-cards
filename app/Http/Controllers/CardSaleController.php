<?php

namespace App\Http\Controllers;

use App\Models\CardSale;
use App\Services\CustomerMessageDeliveryService;
use Illuminate\Support\Facades\DB;

class CardSaleController extends Controller
{
    public function resend(CardSale $cardSale, CustomerMessageDeliveryService $customerMessages)
    {
        $sent = $customerMessages->send(
            $cardSale->customer_phone,
            "إعادة إرسال الكرت. الفئة: {$cardSale->category_name}. الرمز: {$cardSale->pin_code}",
        );

        if (! $sent) {
            return back()->with('error', 'تعذر إرسال رسالة الكرت؛ تحقق من تهيئة مزود الرسائل.');
        }

        return back()->with('success', 'تمت إعادة إرسال رسالة الكرت.');
    }

    public function destroy(CardSale $cardSale)
    {
        DB::transaction(fn () => $cardSale->delete());

        return back()->with('success', 'تم حذف سجل حركة البيع فقط؛ لم يتغير رصيد العميل أو حالة الكرت في المخزون.');
    }
}
